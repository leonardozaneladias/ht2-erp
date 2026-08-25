<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Modules;

use Closure;
use HT2ML\Core\Enums\ModuloAcesso;
use HT2ML\Core\Exceptions\ContribuicoesInvalidas;
use HT2ML\Core\Support\Access\AreaDeAcesso;
use Illuminate\Support\Facades\Log;

/**
 * Registro central de contribuições de módulos-pacote ao core (ver ADR-0015).
 *
 * Módulos de negócio distribuídos como pacotes Composer não editam arquivos do
 * core. Para rotas, cada pacote registra um callback aqui — no register() do seu
 * ServiceProvider, que roda antes do carregamento de routes/admin.php. O grupo
 * autenticado de routes/admin.php itera os callbacks e os executa dentro do
 * middleware admin (tenant, 2FA, inatividade), de modo que as rotas do módulo
 * herdam toda a stack sem duplicá-la.
 *
 * Seeders seguem o mesmo desenho das rotas: cada extensão registra as classes
 * aqui no register(), e o DatabaseSeeder as executa. A ordem importa — os dados
 * de referência precisam existir antes dos seeders de demo do core, que os
 * consomem —, então o registro diz explicitamente se roda antes ou depois.
 *
 * Permissões e itens de menu também passam por aqui. Antes cada extensão copiava
 * do stub o código que mesclava em config('access.modules') e config('admin-menu'),
 * o que espalhava a mesma lógica por todas elas — e foi por isso que o bug de dupla
 * aplicação sob `config:cache` precisou ser corrigido em dois lugares. Agora a
 * extensão apenas *declara* onde suas permissões e itens entram, e o core aplica
 * uma vez só, de forma idempotente, em AppServiceProvider::boot().
 *
 * O estado é estático de propósito: um singleton de container dependeria da ordem
 * de registro entre o provider do core e os providers de pacote — e estes últimos
 * registram primeiro —, o que tornaria o binding frágil. Em testes, use flush()
 * para isolar cenários.
 */
final class ModuleRegistry
{
    /** @var list<Closure> */
    private static array $routeCallbacks = [];

    /** @var array{antes: list<class-string<\Illuminate\Database\Seeder>>, depois: list<class-string<\Illuminate\Database\Seeder>>} */
    private static array $seeders = ['antes' => [], 'depois' => []];

    /** @var array<string, array{label: string, descricao: string, icone: string, variant: string}> */
    private static array $areas = [];

    /** @var array<string, array<string, array{label: string, descricao?: string}>> */
    private static array $permissoes = [];

    /** @var array<string, string> canal:alvo => arquivo:linha da declaração */
    private static array $origens = [];

    /** @var list<ProblemaDeContribuicao> */
    private static array $problemas = [];

    /** @var array<string, array{key: string, title: string, ordem: int|null, items: list<array<string, mixed>>}> */
    private static array $secoes = [];

    /** @var array<string, array{chave: string, secao: string, label: string, icone: string, ordem: int|null}> */
    private static array $grupos = [];

    /** @var array<string, list<array<string, mixed>>> */
    private static array $itensDeMenu = [];

    /** @var array<string, class-string<\Illuminate\Database\Seeder>> */
    private static array $catalogos = [];

    /**
     * Registra um callback que define rotas dentro do grupo autenticado /admin.
     * Deve ser chamado no register() do ServiceProvider do pacote.
     */
    public static function routes(Closure $callback): void
    {
        self::$routeCallbacks[] = $callback;
    }

    /**
     * Callbacks de rota acumulados, consumidos por routes/admin.php.
     *
     * @return list<Closure>
     */
    public static function routeCallbacks(): array
    {
        return self::$routeCallbacks;
    }

    /**
     * Registra um seeder de extensão para ser executado pelo DatabaseSeeder.
     *
     * Deve ser chamado no register() do ServiceProvider da extensão.
     *
     * @param  class-string<\Illuminate\Database\Seeder>  $classe
     * @param  bool  $antesDoCore  true para dados que os seeders do core consomem
     *                             (catálogos de referência, por exemplo)
     */
    public static function seeder(string $classe, bool $antesDoCore = false): void
    {
        $balde = $antesDoCore ? 'antes' : 'depois';

        if (! in_array($classe, self::$seeders[$balde], true)) {
            self::$seeders[$balde][] = $classe;
        }
    }

    /**
     * Seeders de extensão acumulados, consumidos por DatabaseSeeder.
     *
     * @return list<class-string<\Illuminate\Database\Seeder>>
     */
    public static function seeders(bool $antesDoCore = false): array
    {
        return self::$seeders[$antesDoCore ? 'antes' : 'depois'];
    }

    /**
     * Declara uma área do catálogo de permissões — a gaveta que a matriz de
     * acesso usa para agrupar as permissões em algo navegável.
     *
     * O conjunto de áreas é ABERTO: as onze do core vêm do enum ModuloAcesso,
     * e cada módulo acrescenta a sua. Sem este canal, um produto com quatro
     * módulos de negócio empilharia ~90 permissões dentro de 'negocio' — ou
     * editaria o core, que é a doença que a plataforma existe para evitar.
     *
     * Declarar uma área que o produto já descreve em config('access.areas') é
     * no-op: o pacote sugere, quem instala decide.
     */
    public static function areaDeAcesso(
        string $chave,
        string $label,
        string $descricao = '',
        string $icone = 'tabler--box',
        string $variant = 'neutral',
    ): void {
        self::$areas[$chave] = compact('label', 'descricao', 'icone', 'variant');
        self::$origens["area:{$chave}"] = self::origemDaChamada();
    }

    /**
     * Declara as permissões do módulo dentro de uma área do catálogo de acesso.
     *
     * A área precisa existir quando as contribuições forem aplicadas — ou porque
     * é uma das onze do core, ou porque alguém a declarou com areaDeAcesso().
     * A verificação NÃO acontece aqui de propósito: no ato da declaração a
     * config de outra extensão pode ainda não ter sido mesclada, e recusar cedo
     * transforma ordem de boot em erro de configuração. Ver aplicarContribuicoes().
     *
     * @param  array<string, array{label: string, descricao?: string}>  $permissoes
     */
    public static function permissoes(ModuloAcesso|string $area, array $permissoes): void
    {
        $chave = $area instanceof ModuloAcesso ? $area->value : $area;

        self::$permissoes[$chave] = [...(self::$permissoes[$chave] ?? []), ...$permissoes];
        self::$origens["permissoes:{$chave}"] ??= self::origemDaChamada();
    }

    /**
     * Declara uma seção da sidebar — a gaveta de primeiro nível do menu.
     *
     * Mesmo problema das áreas de acesso, no outro eixo: aplicarContribuicoes()
     * só sabia iterar seções que já existiam em config('admin-menu'), e uma
     * seção inexistente era descartada por um `continue`. Um produto com as
     * seções "Escola", "Pedagógico", "Financeiro" e "Cantina" via as telas
     * nascerem e sumirem sem nenhum sinal.
     *
     * `ordem` é sugestão: o valor final é o do banco quando alguém arrastou o
     * item na tela de Gestão de Menus. Faixas: core 100–499, módulos 500+.
     */
    public static function secaoDeMenu(string $chave, string $titulo, ?int $ordem = null): void
    {
        self::$secoes[$chave] = ['key' => $chave, 'title' => $titulo, 'ordem' => $ordem, 'items' => []];
        self::$origens["secao:{$chave}"] = self::origemDaChamada();
    }

    /**
     * Declara um grupo (submenu) dentro de uma seção da sidebar.
     *
     * Antes disto grupos só existiam em `menu_personalizacoes`, criados pela
     * tela — e por isso a disposição padrão precisava de uma Action que
     * hardcodava 'grupo-tab-rh', 'ref-cnaes' e 'rh-departamentos' dentro do
     * core, que é o core conhecendo extensões pelo nome.
     *
     * Um item entra no grupo declarando `'grupo' => 'a-chave'`.
     */
    public static function grupoDeMenu(
        string $chave,
        string $secao,
        string $label,
        string $icone = 'tabler--folder',
        ?int $ordem = null,
    ): void {
        self::$grupos[$chave] = compact('chave', 'secao', 'label', 'icone', 'ordem');
        self::$origens["grupo:{$chave}"] = self::origemDaChamada();
    }

    /**
     * Declara itens de menu do módulo dentro de uma seção da sidebar.
     *
     * A seção precisa existir quando as contribuições forem aplicadas — do
     * config do produto ou declarada com secaoDeMenu(). Ver permissoes() sobre
     * por que a verificação não acontece aqui.
     *
     * @param  list<array<string, mixed>>  $itens
     */
    public static function itensDeMenu(string $secao, array $itens): void
    {
        self::$origens["menu:{$secao}"] ??= self::origemDaChamada();

        // Deduplica por `key` no próprio registro. O registry é estático e a
        // declaração vive no boot() do provider, então cada nova instância da
        // aplicação no mesmo processo redeclara os mesmos itens — sem isto a
        // lista cresce sem parar. As permissões escapam por serem indexadas por
        // chave; o menu é lista e precisa do filtro explícito.
        $presentes = array_column(self::$itensDeMenu[$secao] ?? [], 'key');

        $novos = array_values(array_filter(
            $itens,
            static fn (array $item): bool => ! in_array($item['key'] ?? null, $presentes, true),
        ));

        self::$itensDeMenu[$secao] = [...(self::$itensDeMenu[$secao] ?? []), ...$novos];
    }

    /**
     * Itens de menu declarados para uma seção. Exposto para teste e diagnóstico.
     *
     * @return list<array<string, mixed>>
     */
    public static function aplicados(string $secao): array
    {
        return self::$itensDeMenu[$secao] ?? [];
    }

    /**
     * Aplica as contribuições declaradas ao config(). Chamado uma vez, no boot do
     * core, depois que todos os providers de extensão já registraram.
     *
     * Idempotente por construção: com `config:cache` a configuração é fotografada
     * já mesclada, e este método roda de novo sobre o próprio resultado.
     *
     * É AQUI que tudo é validado, e não no ato de cada declaração. A razão é
     * temporal: quando uma extensão declara, a config de outra pode ainda não
     * ter sido mesclada, então recusar cedo transforma ordem de boot em erro de
     * configuração. O que não puder ser aplicado vira ProblemaDeContribuicao —
     * fatal fora de produção, Log::error em produção. Nunca silencioso.
     */
    public static function aplicarContribuicoes(): void
    {
        self::$problemas = [];

        self::aplicarAreas();
        self::aplicarPermissoes();
        self::aplicarMenu();

        self::reportarProblemas();
    }

    /**
     * Problemas acumulados na última aplicação. Consumido por `ht2ml:doutor`.
     *
     * @return list<ProblemaDeContribuicao>
     */
    public static function problemas(): array
    {
        return self::$problemas;
    }

    /**
     * Declara um catálogo de referência mantido por CSV.
     *
     * Diferente de seeder(): além de semear, o catálogo entra na lista do
     * `referencia:sync`, que é o passo de deploy. Sem este canal, extrair um
     * catálogo do core o tiraria silenciosamente do comando de sincronização.
     *
     * @param  class-string<\Illuminate\Database\Seeder>  $seeder
     */
    public static function catalogoDeReferencia(string $slug, string $seeder): void
    {
        self::$catalogos[$slug] = $seeder;
    }

    /**
     * Catálogos de referência declarados por extensões (slug => seeder).
     *
     * @return array<string, class-string<\Illuminate\Database\Seeder>>
     */
    public static function catalogosDeReferencia(): array
    {
        return self::$catalogos;
    }

    /**
     * Limpa o estado acumulado. Útil em testes para isolar cenários.
     */
    public static function flush(): void
    {
        self::$routeCallbacks = [];
        self::$seeders = ['antes' => [], 'depois' => []];
        self::$areas = [];
        self::$permissoes = [];
        self::$secoes = [];
        self::$grupos = [];
        self::$itensDeMenu = [];
        self::$catalogos = [];
        self::$origens = [];
        self::$problemas = [];
    }

    private static function aplicarAreas(): void
    {
        if (self::$areas === []) {
            return;
        }

        /** @var array<string, array<string, mixed>> $areas */
        $areas = (array) config('access.areas', []);

        foreach (self::$areas as $chave => $dados) {
            // ??=, não sobrescrita: se o produto já descreve a área, a decisão
            // é dele. O pacote sugere; quem instala decide.
            $areas[$chave] ??= $dados;
        }

        config(['access.areas' => $areas]);
    }

    private static function aplicarPermissoes(): void
    {
        if (self::$permissoes === []) {
            return;
        }

        /** @var array<string, array<string, mixed>> $modulos */
        $modulos = (array) config('access.modules', []);

        foreach (self::$permissoes as $area => $permissoes) {
            if (! AreaDeAcesso::existe($area)) {
                self::problema('permissoes', $area, sprintf(
                    "Área de acesso inexistente: '%s'. As %d permissões declaradas nela (%s) ficariam fora do catálogo: o gate negaria todas e a tela não apareceria em lugar nenhum. Declare a área com ModuleRegistry::areaDeAcesso('%s', 'Rótulo') ou use uma existente: %s.",
                    $area,
                    count($permissoes),
                    implode(', ', array_slice(array_keys($permissoes), 0, 3)) . (count($permissoes) > 3 ? ', …' : ''),
                    $area,
                    implode(', ', array_keys(AreaDeAcesso::todas())),
                ));

                continue;
            }

            // array_replace, não merge: chaves iguais são a MESMA permissão
            // reaplicada, e o merge recursivo transformaria 'label' => 'X'
            // em 'label' => ['X', 'X'].
            $modulos[$area] = array_replace($modulos[$area] ?? [], $permissoes);
        }

        config(['access.modules' => $modulos]);
    }

    private static function aplicarMenu(): void
    {
        if (self::$secoes === [] && self::$grupos === [] && self::$itensDeMenu === []) {
            return;
        }

        /** @var list<array<string, mixed>> $menu */
        $menu = (array) config('admin-menu', []);

        $indicePorChave = [];

        foreach ($menu as $i => $secao) {
            if (is_string($secao['key'] ?? null)) {
                $indicePorChave[$secao['key']] = $i;
            }
        }

        // 1. Seções declaradas que ainda não existem entram no fim. A posição
        //    final é decidida por 'ordem' no MenuService, não por esta ordem.
        foreach (self::$secoes as $chave => $secao) {
            if (isset($indicePorChave[$chave])) {
                continue;
            }

            $menu[] = $secao;
            $indicePorChave[$chave] = array_key_last($menu);
        }

        // 2. Grupos declarados entram na seção destino.
        foreach (self::$grupos as $chave => $grupo) {
            if (! isset($indicePorChave[$grupo['secao']])) {
                self::problema('menu', $chave, sprintf(
                    "O grupo '%s' aponta para a seção inexistente '%s'. Declare-a com ModuleRegistry::secaoDeMenu('%s', 'Título') ou use uma existente: %s.",
                    $chave,
                    $grupo['secao'],
                    $grupo['secao'],
                    implode(', ', array_keys($indicePorChave)),
                ));

                continue;
            }

            $i = $indicePorChave[$grupo['secao']];
            /** @var array<string, array<string, mixed>> $existentes */
            $existentes = $menu[$i]['grupos'] ?? [];

            $menu[$i]['grupos'] = [...$existentes, ...array_diff_key([$chave => $grupo], $existentes)];
        }

        // 3. Itens.
        foreach (self::$itensDeMenu as $chave => $itens) {
            if (! isset($indicePorChave[$chave])) {
                self::problema('menu', $chave, sprintf(
                    "Seção de menu inexistente: '%s'. Os %d itens declarados nela (%s) não apareceriam na sidebar. Declare a seção com ModuleRegistry::secaoDeMenu('%s', 'Título') ou use uma existente: %s.",
                    $chave,
                    count($itens),
                    implode(', ', array_slice(array_column($itens, 'key'), 0, 3)) . (count($itens) > 3 ? ', …' : ''),
                    $chave,
                    implode(', ', array_keys($indicePorChave)),
                ));

                continue;
            }

            $i = $indicePorChave[$chave];

            /** @var list<array<string, mixed>> $existentes */
            $existentes = $menu[$i]['items'] ?? [];
            $presentes = array_column($existentes, 'key');

            $novos = array_values(array_filter(
                $itens,
                static fn (array $item): bool => ! in_array($item['key'] ?? null, $presentes, true),
            ));

            $menu[$i]['items'] = [...$existentes, ...$novos];
        }

        config(['admin-menu' => $menu]);
    }

    private static function problema(string $canal, string $alvo, string $mensagem): void
    {
        self::$problemas[] = new ProblemaDeContribuicao(
            canal: $canal,
            alvo: $alvo,
            mensagem: $mensagem,
            origem: self::$origens["{$canal}:{$alvo}"] ?? null,
        );
    }

    private static function reportarProblemas(): void
    {
        if (self::$problemas === []) {
            return;
        }

        // Em produção um erro de declaração não pode virar tela branca: a
        // contribuição já foi pulada acima, aqui só se registra. Fora dela é
        // fatal, e é o que faz o CI pegar.
        if (app()->environment('production')) {
            foreach (self::$problemas as $problema) {
                Log::error('Contribuição de módulo descartada: ' . $problema);
            }

            return;
        }

        throw new ContribuicoesInvalidas(self::$problemas);
    }

    /**
     * Arquivo:linha de quem chamou o canal, para o diagnóstico ser acionável.
     *
     * Cada frame do backtrace guarda a função chamada e o ponto DE ONDE ela foi
     * chamada. Então [1] — o frame do canal — já traz o arquivo do declarante;
     * [2] seria quem chamou o declarante, que em teste é o próprio Pest.
     */
    private static function origemDaChamada(): ?string
    {
        $pilha = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        if (! isset($pilha[1]['file'], $pilha[1]['line'])) {
            return null;
        }

        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', (string) $pilha[1]['file'])
            . ':' . $pilha[1]['line'];
    }
}
