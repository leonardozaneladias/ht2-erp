<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Modules;

use Closure;

/**
 * Declara um módulo e tudo que ele contribui — de uma chave só.
 *
 * Os cinco canais do {@see ModuleRegistry} recebem strings soltas e não têm como
 * saber que três delas pertencem à mesma coisa. Sem essa noção não dá para
 * validar prefixo, nem dizer QUEM declarou uma contribuição inválida — e foi por
 * isso que a permissão de listagem chegou a ser escrita por duas fórmulas que
 * discordavam entre si: `departamentos.listar` num lado, `rh.departamentos.listar`
 * no outro. O gate negava em silêncio e ninguém tinha onde olhar.
 *
 * Aqui a chave do módulo é a fonte, e prefixo de permissão, key de menu, nome de
 * rota, permissão do item e padrão de `active` são DERIVADOS. Uma segunda fórmula
 * deixa de ser possível, em vez de ser proibida.
 *
 * QUANDO NÃO USAR: os canais diretos (`permissoes()`, `itensDeMenu()`) continuam
 * públicos, e a extensão fiscal é a razão. As permissões dela são `cnaes.listar`,
 * sem prefixo de módulo, e as keys de menu são `ref-cnaes` — porque os catálogos
 * foram extraídos do core mantendo as chaves originais. Passá-los pelo builder
 * os renomearia para `fiscal.cnaes.listar` e `fiscal-cnaes`, invalidando
 * permissões já atribuídas a perfis e personalizações de menu já gravadas. Isso
 * é migração de dados em produção, não refatoração. O builder é o caminho feliz
 * de quem nasce agora; os canais são a saída de quem já existe.
 *
 * A aplicação é PREGUIÇOSA: o builder só acumula, e aplicarContribuicoes()
 * executa. Assim a ordem das chamadas não importa — declarar um recurso antes da
 * área funcionaria igual —, e a validação agregada continua sendo a única a
 * recusar. A exceção é rotas(), que precisa registrar no ato: routes/admin.php é
 * carregado bem antes do booted().
 */
final class ModuloBuilder
{
    private string $label;

    private string $icone = 'tabler--box';

    private ?int $ordem = null;

    private ?string $descricaoDaArea = null;

    private bool $declaraArea = false;

    private bool $declaraSecao = false;

    private ?string $areaAlvo = null;

    private ?string $secaoAlvo = null;

    /** @var array<string, array{secao: string|null, label: string, icone: string, ordem: int|null}> */
    private array $grupos = [];

    /** @var list<RecursoBuilder> */
    private array $recursos = [];

    public function __construct(public readonly string $chave)
    {
        $this->label = str($chave)->headline()->value();
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function icone(string $icone): self
    {
        $this->icone = $icone;

        return $this;
    }

    public function ordem(int $ordem): self
    {
        $this->ordem = $ordem;

        return $this;
    }

    /** Declara a área de acesso do módulo, com a chave do próprio módulo. */
    public function areaDeAcesso(string $descricao = ''): self
    {
        $this->declaraArea = true;
        $this->descricaoDaArea = $descricao;

        return $this;
    }

    /** Declara a seção de menu do módulo, com a chave do próprio módulo. */
    public function secaoDeMenu(): self
    {
        $this->declaraSecao = true;

        return $this;
    }

    /**
     * Contribui para uma área que já existe, em vez de declarar a sua.
     *
     * A convenção é 1:1 entre módulo, área e seção — mas é convenção, não
     * invariante: 'tabelas_auxiliares' agrupa CNAE (extensão fiscal) e Estados
     * (core), então atravessa pacotes por natureza.
     */
    public function naArea(string $chave): self
    {
        $this->areaAlvo = $chave;

        return $this;
    }

    /** Contribui para uma seção de menu que já existe. */
    public function naSecao(string $chave): self
    {
        $this->secaoAlvo = $chave;

        return $this;
    }

    public function grupoDeMenu(
        string $chave,
        string $label,
        string $icone = 'tabler--folder',
        ?int $ordem = null,
        ?string $secao = null,
    ): self {
        $this->grupos[$chave] = compact('secao', 'label', 'icone', 'ordem');

        return $this;
    }

    /**
     * Rotas do módulo, dentro do grupo autenticado /admin.
     *
     * Aplicada NA HORA, ao contrário do resto: routes/admin.php é carregado no
     * boot do core, muito antes do booted() onde as contribuições são aplicadas.
     */
    public function rotas(string|Closure $rotas, EscopoDeRota $escopo = EscopoDeRota::Admin): self
    {
        ModuleRegistry::routes($rotas instanceof Closure
            ? $rotas
            : static function () use ($rotas): void {
                require $rotas;
            }, $escopo);

        return $this;
    }

    /**
     * Rotas públicas do módulo: stack `web`, sem login e sem prefixo.
     *
     * Açúcar sobre rotas(..., EscopoDeRota::Publico) porque a alternativa é o
     * autor do módulo importar o enum para dizer uma coisa que o nome do método
     * já diz.
     */
    public function rotasPublicas(string|Closure $rotas): self
    {
        return $this->rotas($rotas, EscopoDeRota::Publico);
    }

    /** Webhooks do módulo: sem sessão, sem CSRF, sob /webhooks. */
    public function rotasDeWebhook(string|Closure $rotas): self
    {
        return $this->rotas($rotas, EscopoDeRota::Webhook);
    }

    /**
     * Abre um recurso — e já o coleta.
     *
     * A coleta é aqui, e não em `registrar()`, de propósito. Nas duas ordens
     * possíveis alguém uma hora esquece a chamada final; o que muda é o que se
     * vê quando isso acontece. Coletando aqui, um builder abandonado vira um
     * recurso com rótulo derivado da chave e ícone padrão: feio, visível na
     * sidebar, corrigido em minutos. Coletando em `registrar()`, viraria um
     * recurso que não existe — sem permissão, sem item de menu e sem erro,
     * que é exatamente a falha silenciosa que esta base foi feita para acabar.
     */
    public function recurso(string $chave): RecursoBuilder
    {
        return $this->recursos[] = new RecursoBuilder($this, $chave);
    }

    /**
     * Lê área, seção, grupos e recursos da config publicável do pacote.
     *
     * Sem isto, cada ServiceProvider repetiria o mesmo laço de quarenta linhas
     * sobre `config('x.grupos')` e `config('x.recursos')` — e boilerplate
     * repetido em cinco pacotes é a forma como as convenções divergem. O
     * provider fica com três linhas: quem é o módulo, onde está a config dele,
     * e onde estão as rotas.
     *
     * A config continua sendo o ponto de customização por cliente (ADR-0015):
     * quem publica decide rótulo, ícone, ordem, grupo e onde as contribuições
     * entram. O que ela deixou de conter é o que era DERIVÁVEL.
     */
    public function deConfig(string $namespace): self
    {
        $area = config("{$namespace}.modulo_acesso");
        $secao = config("{$namespace}.secao_menu");

        if (is_string($area) && $area !== '') {
            $this->naArea($area);
        }

        if (is_string($secao) && $secao !== '') {
            $this->naSecao($secao);
        }

        /** @var array<string, array<string, mixed>> $grupos */
        $grupos = (array) config("{$namespace}.grupos", []);

        foreach ($grupos as $chave => $grupo) {
            $this->grupoDeMenu(
                (string) $chave,
                (string) ($grupo['label'] ?? $chave),
                (string) ($grupo['icone'] ?? 'tabler--folder'),
                isset($grupo['ordem']) ? (int) $grupo['ordem'] : null,
                isset($grupo['secao']) ? (string) $grupo['secao'] : null,
            );
        }

        /** @var array<string, array<string, mixed>> $recursos */
        $recursos = (array) config("{$namespace}.recursos", []);

        foreach ($recursos as $chave => $recurso) {
            $builder = $this->recurso((string) $chave)
                ->label((string) ($recurso['label'] ?? $chave))
                ->icone((string) ($recurso['icone'] ?? 'tabler--folder'));

            if (isset($recurso['singular'])) {
                $builder->singular((string) $recurso['singular']);
            }

            if (isset($recurso['grupo'])) {
                $builder->noGrupo((string) $recurso['grupo']);
            }

            if (isset($recurso['ordem'])) {
                $builder->ordem((int) $recurso['ordem']);
            }

            if (isset($recurso['rota_base'])) {
                $builder->rotaBase((string) $recurso['rota_base']);
            }

            if ($recurso['sem_lixeira'] ?? false) {
                $builder->semLixeira();
            }

            if ($recurso['sem_menu'] ?? false) {
                $builder->semMenu();
            }

            // Sem `registrar()` no fim: `recurso()` já coletou, então a chamada
            // não teria efeito nenhum aqui — e o PHPStan diz isso em voz alta.
        }

        return $this;
    }

    /** A área onde as permissões deste módulo entram. */
    public function areaEfetiva(): string
    {
        return $this->areaAlvo ?? $this->chave;
    }

    /** A seção de menu onde os itens deste módulo entram. */
    public function secaoEfetiva(): string
    {
        return $this->secaoAlvo ?? $this->chave;
    }

    /**
     * Empurra tudo para os canais do registry. Chamado por aplicarContribuicoes().
     */
    public function aplicar(): void
    {
        if ($this->declaraArea) {
            ModuleRegistry::areaDeAcesso(
                $this->chave,
                $this->label,
                (string) $this->descricaoDaArea,
                $this->icone,
            );
        }

        if ($this->declaraSecao) {
            ModuleRegistry::secaoDeMenu($this->chave, $this->label, $this->ordem);
        }

        foreach ($this->grupos as $chave => $grupo) {
            ModuleRegistry::grupoDeMenu(
                $chave,
                $grupo['secao'] ?? $this->secaoEfetiva(),
                $grupo['label'],
                $grupo['icone'],
                $grupo['ordem'],
            );
        }

        $permissoes = [];
        $itens = [];

        foreach ($this->recursos as $recurso) {
            $permissoes = [...$permissoes, ...$recurso->permissoes()];

            // A permissão existe mesmo sem item de menu: quem tem `--skip-menu`
            // ainda precisa que o gate deixe passar.
            if ($recurso->temMenu()) {
                $itens[] = $recurso->itemDeMenu();
            }
        }

        if ($permissoes !== []) {
            ModuleRegistry::permissoes($this->areaEfetiva(), $permissoes);
        }

        if ($itens !== []) {
            ModuleRegistry::itensDeMenu($this->secaoEfetiva(), $itens);
        }
    }
}
