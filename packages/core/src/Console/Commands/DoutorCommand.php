<?php

declare(strict_types=1);

namespace HT2ML\Core\Console\Commands;

use HT2ML\Core\Support\Access\AreaDeAcesso;
use HT2ML\Core\Support\Access\PermissionRegistry;
use HT2ML\Core\Support\Menu\IconesMenu;
use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Diagnóstico da configuração dos módulos: o que está declarado, mas não fecha.
 *
 * As convenções da plataforma — prefixo de permissão, chave de área, seção de
 * menu, nome de rota — eram acordos escritos em prosa. Um acordo que ninguém
 * executa é violado na primeira pressa, e a violação só aparece quando alguém
 * abre a tela e ela está vazia. Aqui cada convenção vira uma pergunta com
 * resposta binária, e o CI reprova em vez de a instalação degradar em silêncio.
 *
 * Exit 1 quando há qualquer problema — é o que torna a regra executável.
 */
final class DoutorCommand extends Command
{
    protected $signature = 'ht2ml:doutor {--json : Emite o relatório em JSON}';

    protected $description = 'Verifica se as contribuições dos módulos fecham: áreas, seções, grupos, permissões, rotas e ícones.';

    /** @var list<array{verificacao: string, alvo: string, mensagem: string}> */
    private array $problemas = [];

    public function handle(PermissionRegistry $permissoes): int
    {
        $this->contribuicoesAplicadas();
        $this->areasDasPermissoes();
        $this->gruposDosItens();
        $this->permissoesDosItens($permissoes);
        $this->rotasDosItens();
        $this->iconesDosItens();
        $this->escoposComHospedeiro();
        $this->manifestosCoerentes();

        if ($this->option('json')) {
            $this->line((string) json_encode($this->problemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $this->problemas === [] ? self::SUCCESS : self::FAILURE;
        }

        if ($this->problemas === []) {
            $this->components->info('Tudo fecha: áreas, seções, grupos, permissões, rotas, ícones, escopos e manifestos.');

            return self::SUCCESS;
        }

        foreach ($this->problemas as $problema) {
            $this->components->error("{$problema['verificacao']} — {$problema['alvo']}");
            $this->line("  {$problema['mensagem']}");
        }

        $this->newLine();
        $this->components->error(sprintf('%d problema(s) encontrado(s).', count($this->problemas)));

        return self::FAILURE;
    }

    private function problema(string $verificacao, string $alvo, string $mensagem): void
    {
        $this->problemas[] = compact('verificacao', 'alvo', 'mensagem');
    }

    /**
     * O que o ModuleRegistry já descartou na aplicação. Em produção estes só
     * apareciam no log; aqui eles têm uma superfície de comando.
     */
    private function contribuicoesAplicadas(): void
    {
        foreach (ModuleRegistry::problemas() as $problema) {
            $this->problema(
                'contribuição descartada',
                $problema->alvo,
                (string) $problema,
            );
        }
    }

    /**
     * O manifesto do pacote e o código dele dizem a mesma chave?
     *
     * Um pacote pode declarar `extra.ht2ml.chave = escola` e o ServiceProvider
     * registrar 'escolas'. As duas grafias existem, nenhuma reclama, e o efeito
     * é permissão num prefixo e menu em outro. No monorepo isso é pego por
     * tests/Arch/CoerenciaDoModuloTest; num PRODUTO, onde os pacotes chegam por
     * Composer e não há suíte, quem pega é isto.
     */
    private function manifestosCoerentes(): void
    {
        $instalados = base_path('vendor/composer/installed.json');

        if (! is_file($instalados)) {
            return;
        }

        /** @var array{packages?: list<array<string, mixed>>} $dados */
        $dados = (array) json_decode((string) file_get_contents($instalados), true);
        $declaradas = ModuleRegistry::chavesDeModulo();

        foreach ($dados['packages'] ?? [] as $pacote) {
            /** @var array<string, string> $ht2ml */
            $ht2ml = (array) ((($pacote['extra'] ?? [])['ht2ml']) ?? []);

            if (($ht2ml['tipo'] ?? null) !== 'modulo') {
                continue;
            }

            $chave = (string) ($ht2ml['chave'] ?? '');
            $nome = (string) ($pacote['name'] ?? '?');

            if ($chave === '') {
                $this->problema(
                    'manifesto incoerente',
                    $nome,
                    'Declara extra.ht2ml.tipo=modulo sem extra.ht2ml.chave. A chave é a fonte única '
                    . 'de prefixo de permissão, seção de menu, namespace de view e rota (ADR-0021).',
                );

                continue;
            }

            if (in_array($chave, $declaradas, true)) {
                continue;
            }

            $this->problema(
                'manifesto incoerente',
                $nome,
                sprintf(
                    'O manifesto diz que este pacote carrega o módulo \'%s\', mas nenhum '
                    . 'ModuleRegistry::modulo(\'%s\') foi declarado no boot. Declaradas: %s.',
                    $chave,
                    $chave,
                    $declaradas === [] ? 'nenhuma' : implode(', ', $declaradas),
                ),
            );
        }
    }

    /**
     * Todo escopo com contribuição precisa do arquivo de rota que o executa.
     *
     * Um módulo pode registrar um webhook num core antigo, que não tem
     * routes/webhook.php: o callback fica no registry, ninguém o executa, e a
     * rota simplesmente não existe. O gateway recebe 404, a aplicação não
     * registra nada, e não há onde olhar. Aqui há.
     */
    private function escoposComHospedeiro(): void
    {
        foreach (ModuleRegistry::escoposComRotas() as $escopo) {
            $arquivo = __DIR__ . "/../../../routes/{$escopo->value}.php";

            if (is_file($arquivo)) {
                continue;
            }

            $this->problema(
                'escopo sem hospedeiro',
                $escopo->value,
                sprintf(
                    'Há rotas de %s registradas, mas o core não tem routes/%s.php para executá-las. '
                    . 'As rotas não existem — e o sintoma é 404 sem nada no log.',
                    $escopo->rotulo(),
                    $escopo->value,
                ),
            );
        }
    }

    /** Toda gaveta do catálogo de permissões precisa existir em access.areas. */
    private function areasDasPermissoes(): void
    {
        /** @var array<string, array<string, mixed>> $modulos */
        $modulos = (array) config('access.modules', []);

        foreach (array_keys($modulos) as $area) {
            if (AreaDeAcesso::existe((string) $area)) {
                continue;
            }

            $this->problema(
                'área de acesso',
                (string) $area,
                sprintf(
                    "%d permissões estão na área '%s', que não existe em config('access.areas'). A matriz de acesso vai mostrá-las sob um rótulo derivado, sem descrição nem ícone.",
                    count($modulos[$area]),
                    $area,
                ),
            );
        }
    }

    /** Todo item que declara `grupo` precisa apontar para um grupo declarado. */
    private function gruposDosItens(): void
    {
        $declarados = [];

        foreach ($this->secoes() as $secao) {
            foreach (array_keys((array) ($secao['grupos'] ?? [])) as $chave) {
                $declarados[] = (string) $chave;
            }
        }

        foreach ($this->itens() as $item) {
            $grupo = $item['grupo'] ?? null;

            if (! is_string($grupo) || in_array($grupo, $declarados, true)) {
                continue;
            }

            $this->problema(
                'grupo de menu',
                (string) $item['key'],
                sprintf(
                    "O item aponta para o grupo '%s', que nenhuma seção declara. Ele vai renderizar solto na seção, e ninguém vai saber por quê. Grupos declarados: %s.",
                    $grupo,
                    $declarados === [] ? '(nenhum)' : implode(', ', $declarados),
                ),
            );
        }
    }

    /** A permissão que controla o item precisa estar no catálogo. */
    private function permissoesDosItens(PermissionRegistry $registry): void
    {
        foreach ($this->itens() as $item) {
            $permissao = $item['permission'] ?? null;

            if (! is_string($permissao) || $registry->existe($permissao)) {
                continue;
            }

            $this->problema(
                'permissão do item',
                (string) $item['key'],
                sprintf(
                    "O item exige '%s', que não está no catálogo. O gate nega para todo mundo exceto super-admin, então a tela existe e ninguém a alcança.",
                    $permissao,
                ),
            );
        }
    }

    /** A rota nomeada precisa existir, ou a sidebar quebra ao renderizar. */
    private function rotasDosItens(): void
    {
        foreach ($this->itens() as $item) {
            $rota = $item['route'] ?? null;

            if (! is_string($rota) || Route::has($rota)) {
                continue;
            }

            $this->problema(
                'rota do item',
                (string) $item['key'],
                sprintf("O item aponta para a rota nomeada '%s', que não está registrada. route() lança na renderização da sidebar — a página inteira cai.", $rota),
            );
        }
    }

    /**
     * O ícone precisa estar na lista curada — que é o seletor da tela de Gestão
     * de Menus e, ao mesmo tempo, o que o grid de sugestões renderiza
     * literalmente para o Tailwind incluir a classe no bundle.
     *
     * Um ícone usado no menu e ausente da lista é um beco sem saída: quem
     * trocar o ícone do item não consegue voltar ao original, porque a
     * validação `Rule::in(IconesMenu::disponiveis())` recusa o valor que o
     * próprio config declara.
     */
    private function iconesDosItens(): void
    {
        $disponiveis = IconesMenu::disponiveis();

        $checar = function (?string $icone, string $alvo) use ($disponiveis): void {
            if ($icone === null || in_array($icone, $disponiveis, true)) {
                return;
            }

            $this->problema(
                'ícone',
                $alvo,
                sprintf(
                    "'%s' está fora de IconesMenu::disponiveis(). A tela de Gestão de Menus não o oferece, então trocar o ícone deste item é irreversível — e num produto que instala o core por Composer a classe pode nem entrar no bundle do Tailwind.",
                    $icone,
                ),
            );
        };

        foreach ($this->secoes() as $secao) {
            foreach ((array) ($secao['grupos'] ?? []) as $chave => $grupo) {
                $checar(is_string($grupo['icone'] ?? null) ? $grupo['icone'] : null, "grupo {$chave}");
            }
        }

        foreach ($this->itens() as $item) {
            $checar(is_string($item['icon'] ?? null) ? $item['icon'] : null, "item {$item['key']}");
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function secoes(): array
    {
        /** @var list<array<string, mixed>> */
        return (array) config('admin-menu', []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function itens(): array
    {
        $itens = [];

        foreach ($this->secoes() as $secao) {
            foreach ((array) ($secao['items'] ?? []) as $item) {
                if (is_array($item) && isset($item['key'])) {
                    $itens[] = $item;
                }
            }
        }

        return $itens;
    }
}
