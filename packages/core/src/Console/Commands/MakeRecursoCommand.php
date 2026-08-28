<?php

declare(strict_types=1);

namespace HT2ML\Core\Console\Commands;

use HT2ML\Core\Support\Generator\CampoModulo;
use HT2ML\Core\Support\Generator\EspecificacaoModulo;
use HT2ML\Core\Support\Generator\Extensao;
use HT2ML\Core\Support\Generator\ResolvedorDeStubs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Gera o CRUD completo de um RECURSO já no padrão do projeto (Migration,
 * Factory, Model, Enum de status, DTO, FormRequests + Rules, Actions, Service,
 * Policy, Livewire Index/Form/Table, views e teste Feature), além de ligar
 * rotas, permissões e menu. Objetivo: o dev só preenche a regra de negócio.
 *
 *   php artisan make:recurso Produto --modulo=escola \
 *     --fields="nome:string, sku:string:unique, preco:money, descricao:text:nullable, status:enum(rascunho|publicado|arquivado)" \
 *     --tenant
 *
 * Chamava-se `make:modulo` até 2026-08-28. O nome era o quarto sentido
 * simultâneo de "módulo" no repositório e o mais enganoso: o argumento era uma
 * ENTIDADE no singular (Funcionario), não uma área de negócio. Ver ADR-0021.
 *
 * Tipos: string, text, integer, money, boolean, date, datetime, email, cnpj,
 * cpf, cep, phone, enum(a|b|c). Modificadores: nullable, unique.
 */
final class MakeRecursoCommand extends Command
{
    /*
     * Os cinco comentários-marcador que o gerador procura para saber ONDE
     * escrever. Constantes públicas, e não literais repetidos, porque o teste
     * que verifica os marcadores já saiu de sincronia uma vez: pinava o texto
     * antigo enquanto o gerador procurava o novo, e a divergência só apareceu
     * na suíte. Com a constante, o teste não tem como afirmar um marcador que
     * o gerador não usa.
     */

    /** No config do pacote: uma entrada em 'recursos'. */
    public const MARCADOR_RECURSOS = '// make:recurso insere os recursos do módulo acima desta linha';

    /** No config/access.php do produto (recurso fora de pacote). */
    public const MARCADOR_PERMISSOES = '// make:recurso insere permissões de negócio acima desta linha';

    /** No config/admin-menu.php do produto (recurso fora de pacote). */
    public const MARCADOR_MENU = '// make:recurso insere itens de menu acima desta linha';

    /** No routes/admin.php, do produto ou do pacote. */
    public const MARCADOR_ROTAS = '// make:recurso insere as rotas do recurso abaixo desta linha';

    /** No ServiceProvider do pacote: componentes Livewire e Gate::policy. */
    public const MARCADOR_PROVIDER = '// make:recurso registra os componentes Livewire e as policies do recurso acima desta linha';

    protected $signature = 'make:recurso
        {nome : Nome do recurso no singular, PascalCase (ex.: Produto)}
        {--fields= : Lista "nome:tipo:modificador" separada por vírgula}
        {--tenant : Vincula o recurso à empresa ativa (trait BelongsToEmpresa)}
        {--modulo= : Chave do módulo que recebe o recurso, kebab-case (ex.: rh, fiscal-br)}
        {--menu= : Rótulo do item de menu (default: nome no plural)}
        {--menu-icon=tabler--folder : Ícone do item de menu (Tabler)}
        {--skip-menu : Não injeta o item no menu lateral (config/admin-menu.php)}
        {--sem-soft-delete : Desativa o soft-delete (por padrão registros são recuperáveis via deleted_at)}
        {--force : Sobrescreve arquivos existentes}
        {--module= : REMOVIDO em 2026-08-28 — use --modulo com a chave kebab-case}';

    protected $description = 'Gera o CRUD completo de um recurso (model, DTO, actions, Livewire, PowerGrid, views, teste, rotas, permissões e item de menu).';

    /** @var list<string> caminhos gerados (para o resumo final) */
    private array $criados = [];

    /** @var list<string> caminhos pulados por já existirem */
    private array $pulados = [];

    /** @var list<string> injeções que não aconteceram por falta de marcador */
    private array $naoLigados = [];

    /** @var list<string> arquivos escritos, para formatar no fim */
    private array $escritos = [];

    /** null = não tentou; false = Pint ausente ou falhou */
    private ?bool $formatado = null;

    public function handle(): int
    {
        if ((string) $this->option('module') !== '') {
            $this->error('A opção --module não existe mais. Use --modulo, com a chave kebab-case do módulo:');
            $this->line('  php artisan make:recurso ' . $this->argument('nome') . ' --modulo=rh');

            return self::FAILURE;
        }

        $nome = Str::studly(Str::singular((string) $this->argument('nome')));

        if ($nome === '') {
            $this->error('Informe um nome de recurso válido (ex.: Produto).');

            return self::FAILURE;
        }

        $campos = $this->parseFields((string) $this->option('fields'));

        $pacote = null;
        $modulo = (string) $this->option('modulo');
        if ($modulo !== '') {
            $pacote = Extensao::paraNome($modulo);
            if (! File::isDirectory(base_path($pacote->dir))) {
                $this->error("Módulo {$pacote->pacote} não encontrado em {$pacote->dir}.");
                $this->line("Crie a casca primeiro:  php artisan make:modulo {$pacote->slug}");

                return self::FAILURE;
            }
        }

        if ($pacote === null && ! $this->produtoRecebeRecurso()) {
            return self::FAILURE;
        }

        $spec = new EspecificacaoModulo($nome, $campos, tenant: (bool) $this->option('tenant'), pacote: $pacote, softDelete: ! (bool) $this->option('sem-soft-delete'));

        // Os stubs viajam DENTRO do pacote; o produto sobrescreve arquivo a
        // arquivo em stubs/modulo/. Ver ResolvedorDeStubs.
        $stubs = new ResolvedorDeStubs('modulo');

        $repl = $this->montarReplacements($spec);
        $arquivos = $this->mapaArquivos($spec);
        $this->resolverMigrationExistente($spec, $arquivos);

        foreach ($arquivos as $stub => $destino) {
            $this->gerarArquivo($stubs, $stub, $destino, $repl);
        }

        if ($spec->pacote !== null) {
            $this->integrarNoPacote($spec);
        } else {
            $this->injetarRotas($spec);
            $this->injetarPermissoes($spec);
            $this->injetarMenu($spec);
        }

        $this->formatar();
        $this->resumo($spec);

        // FAILURE quando algo não foi ligado: os arquivos existem, mas a tela
        // não abre. Um script de setup que só olha o exit code precisa parar
        // aqui — antes, ele seguia em frente com uma tela inalcançável.
        return $this->naoLigados === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Sem --modulo, a tela é ligada em arquivos DO PRODUTO. Eles ainda existem?
     *
     * Depois da extração, não: routes/admin.php, config/access.php e
     * config/admin-menu.php vivem dentro de ht2ml/core, e o produto não edita o
     * core (ADR-0022). O que acontecia era pior que não funcionar — o comando
     * escrevia os dezenove arquivos e SÓ ENTÃO morria com FileNotFoundException
     * e stack trace, deixando o produto sujo e sem explicação. E este era o
     * caminho impresso no TL;DR de docs/criar-recurso.md, de CLAUDE.md e do
     * CONTRIBUTING: o primeiro comando que alguém digitaria.
     *
     * A checagem fica aqui, antes de escrever um byte, e o produto que de fato
     * possua os três arquivos continua funcionando como antes.
     */
    private function produtoRecebeRecurso(): bool
    {
        $exigidos = ['routes/admin.php', 'config/access.php', 'config/admin-menu.php'];

        $faltando = array_values(array_filter(
            $exigidos,
            static fn (string $arquivo): bool => ! File::isFile(base_path($arquivo)),
        ));

        if ($faltando === []) {
            return true;
        }

        $this->error('Este recurso não teria onde ser ligado.');
        $this->newLine();
        $this->line('Sem <options=bold>--modulo</>, o gerador escreve em app/ e liga a tela em arquivos');
        $this->line('do PRODUTO. Este produto não tem: ' . implode(', ', $faltando) . '.');
        $this->newLine();
        $this->line('Eles vivem dentro de ht2ml/core desde a extração, e o produto não edita');
        $this->line('o core (ADR-0022). Um recurso pertence a um módulo — crie o módulo e');
        $this->line('gere o recurso dentro dele:');
        $this->newLine();
        $this->line('  <fg=green>php artisan make:modulo escola</>');
        $this->line('  <fg=green>composer require ht2ml/extensao-escola:@dev</>');
        $this->line(sprintf(
            '  <fg=green>php artisan make:recurso %s --modulo=escola --fields="%s"</>',
            $this->argument('nome'),
            (string) ($this->option('fields') ?: 'nome:string'),
        ));

        return false;
    }

    /**
     * Passa o Pint nos arquivos recém-escritos.
     *
     * Um stub não tem como sair limpo sozinho: `ordered_imports` depende do
     * namespace do destino — `App\Enums` vem antes de `HT2ML\Core`, mas
     * `HT2ML\Sonda\Enums` vem depois —, e o mesmo stub serve os dois casos.
     * Some-se a isso a linha que sobra quando um token opcional (multi-empresa)
     * é substituído por vazio. Medido num recurso recém-gerado: 7 dos 19
     * arquivos reprovavam em `pint --test`. Como o CI deste repositório roda
     * exatamente isso, um recurso novo nascia com o CI vermelho.
     *
     * Rodar o formatador do projeto é mais honesto que perseguir cada regra no
     * stub, porque é a mesma configuração que o CI usa — se ela mudar, isto
     * acompanha. Quando o Pint não está instalado (produto que só instalou o
     * core em produção), a instrução manual continua no resumo.
     */
    private function formatar(): void
    {
        if ($this->escritos === []) {
            return;
        }

        $pint = base_path('vendor/bin/pint');

        if (! File::isFile($pint)) {
            $this->formatado = false;

            return;
        }

        $resultado = Process::path(base_path())->run([$pint, ...$this->escritos]);

        $this->formatado = $resultado->successful();
    }

    /**
     * Reporta uma injeção que não aconteceu por falta do comentário-marcador.
     *
     * Duas delas eram silenciosas, cada uma de um jeito, e ambas piores que um
     * erro: registrarNoProviderPacote() voltava sem dizer nada, e as duas de
     * rota chamavam str_replace sem casar coisa alguma, reescreviam o arquivo
     * idêntico e AINDA anunciavam "criado (rotas)". O resultado é o mesmo dos
     * dois jeitos — uma tela gerada e inalcançável —, e nada ficava vermelho.
     */
    private function marcadorAusente(string $arquivo, string $marcador, string $qual): void
    {
        $this->naoLigados[] = "{$arquivo} — marcador {$qual}";

        $this->error("Falta o marcador {$qual} em {$arquivo}.");
        $this->line('  Acrescente esta linha onde o bloco deve entrar:');
        $this->line("  <fg=yellow>{$marcador}</>");
    }

    /**
     * @return list<CampoModulo>
     */
    private function parseFields(string $fields): array
    {
        $tokens = array_filter(array_map('trim', explode(',', $fields)), static fn (string $valor): bool => $valor !== '');

        return array_values(array_map(
            static fn (string $token): CampoModulo => CampoModulo::deToken($token),
            $tokens,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function montarReplacements(EspecificacaoModulo $spec): array
    {
        $textoHelper = $spec->dtoUsaTextoHelper()
            ? "\$texto = static fn (string \$chave): ?string => isset(\$data[\$chave]) && \$data[\$chave] !== ''\n            ? (string) \$data[\$chave]\n            : null;\n\n        "
            : '';

        $htmlHelper = $spec->dtoUsaHtmlHelper()
            ? "\$html = static fn (string \$chave): ?string => isset(\$data[\$chave]) && trim((string) \$data[\$chave]) !== ''\n            ? \\HT2ML\\Core\\Support\\Html\\HtmlSanitizer::clean((string) \$data[\$chave])\n            : null;\n\n        "
            : '';

        return [
            ...$spec->tokens(),
            '__MIGRATION_COLUNAS__' => $spec->migrationColunas(),
            '__MIGRATION_INDICES__' => $spec->migrationIndices(),
            '__MODEL_FILLABLE__' => $spec->modelFillable(),
            '__MODEL_DATE_PROPERTIES__' => $spec->modelDateProperties(),
            '__MODEL_CASTS__' => $spec->modelCasts(),
            '__DTO_PROPS__' => $spec->dtoProps(),
            '__DTO_HTML_HELPER__' => $htmlHelper,
            '__DTO_TEXTO_HELPER__' => $textoHelper,
            '__DTO_FROMARRAY__' => $spec->dtoFromArray(),
            '__DTO_PARAMODEL__' => $spec->dtoParaModel(),
            '__FORM_PROPS__' => $spec->formProps(),
            '__FORM_MOUNT_LOAD__' => $spec->formMountLoad(),
            '__RULES_BODY__' => $spec->regrasBody(),
            '__RULE_IMPORT__' => $spec->regrasUsaRule() ? 'use Illuminate\Validation\Rule;' : '',
            '__VALIDATION_ATTRS__' => $spec->validationAttributes(),
            '__FACTORY_DEF__' => $spec->factoryDefinition(),
            '__CAMPOS_DECLARATIVOS__' => $spec->camposDeclarativos(),
            '__FICHA_CAMPOS__' => $spec->fichaCampos(),
            '__FORM_BODY__' => $spec->formBody(),
            '__ENUM_CASES__' => $spec->enumCases(),
            '__ENUM_LABEL_ARMS__' => $spec->enumLabelArms(),
            '__ENUM_VARIANT_ARMS__' => $spec->enumVariantArms(),
            '__TEST_TENANT_SETUP__' => $spec->testTenantSetup(),
            '__TEST_SET_CAMPOS__' => $spec->testSets(),
            '__POLICY_RESTORE_FORCE__' => $spec->metodosPolicyLixeira(),
            '__FACTORY_TRASHED__' => $spec->factoryTrashed(),
            '__TEST_SOFT_DELETE__' => $spec->testeSoftDelete(),
        ];
    }

    /**
     * Mapa stub => caminho de destino (relativo à raiz do projeto).
     *
     * @return array<string, string>
     */
    private function mapaArquivos(EspecificacaoModulo $spec): array
    {
        if ($spec->pacote !== null) {
            return $this->mapaArquivosPacote($spec);
        }

        $studly = $spec->studly;
        $studlyPlural = $spec->studlyPlural;
        $snake = $spec->snake();
        $snakePlural = $spec->snakePlural();
        $migracao = now()->format('Y_m_d_His');

        // Com soft-delete, o _acoes vem do stub com a estrutura de lixeira.

        $mapa = [
            'migration.stub' => "database/migrations/{$migracao}_create_{$spec->tabela()}_table.php",
            'factory.stub' => "database/factories/{$studly}Factory.php",
            'model.stub' => "app/Models/{$studly}.php",
            'enum.stub' => "app/Enums/{$spec->statusEnumShort()}.php",
            'dto.stub' => "app/DTOs/Admin/{$studly}DTO.php",
            'rules.stub' => "app/Http/Requests/Admin/{$studly}Rules.php",
            'store-request.stub' => "app/Http/Requests/Admin/Store{$studly}Request.php",
            'update-request.stub' => "app/Http/Requests/Admin/Update{$studly}Request.php",
            'create-action.stub' => "app/Actions/Admin/Create{$studly}Action.php",
            'update-action.stub' => "app/Actions/Admin/Update{$studly}Action.php",
            'service.stub' => "app/Services/Admin/{$studly}Service.php",
            'policy.stub' => "app/Policies/{$studly}Policy.php",
            'livewire-index.stub' => "app/Livewire/Admin/{$studlyPlural}/Index{$studly}.php",
            'livewire-form.stub' => "app/Livewire/Admin/{$studlyPlural}/Form{$studly}.php",
            'livewire-table.stub' => "app/Livewire/Admin/{$studlyPlural}/{$studly}Table.php",
            'view-index.stub' => "resources/views/livewire/admin/{$snakePlural}/index-{$snakePlural}.blade.php",
            'view-form.stub' => "resources/views/livewire/admin/{$snakePlural}/form-{$snake}.blade.php",
            'view-ficha.stub' => "resources/views/livewire/admin/{$snakePlural}/_ficha.blade.php",
            'test.stub' => "tests/Feature/Admin/{$studlyPlural}/{$studly}CrudTest.php",
        ];

        return $mapa;
    }

    /**
     * Migrations têm timestamp no nome: a checagem por caminho exato não pega
     * uma migration anterior do mesmo módulo. Aqui procuramos por
     * `*_create_{tabela}_table.php` — pulando (ou removendo, com --force) para
     * não criar migrations duplicadas ao re-gerar.
     *
     * @param  array<string, string>  $arquivos
     */
    private function resolverMigrationExistente(EspecificacaoModulo $spec, array &$arquivos): void
    {
        $existentes = glob(base_path("database/migrations/*_create_{$spec->tabela()}_table.php")) ?: [];

        if ($existentes === []) {
            return;
        }

        if (! $this->option('force')) {
            unset($arquivos['migration.stub']);
            $this->pulados[] = 'migration (já existe para a tabela)';

            return;
        }

        foreach ($existentes as $arquivo) {
            File::delete($arquivo);
        }
    }

    /**
     * @param  array<string, string>  $repl
     */
    private function gerarArquivo(ResolvedorDeStubs $stubs, string $stub, string $destinoRelativo, array $repl): void
    {
        $destino = base_path($destinoRelativo);

        if (File::exists($destino) && ! $this->option('force')) {
            $this->pulados[] = $destinoRelativo;

            return;
        }

        $conteudo = strtr($stubs->conteudo($stub), $repl);

        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $conteudo);

        $this->criados[] = $destinoRelativo;
        $this->escritos[] = $destinoRelativo;
    }

    private function injetarRotas(EspecificacaoModulo $spec): void
    {
        $arquivo = base_path('routes/admin.php');
        $conteudo = (string) File::get($arquivo);

        $prefixo = $spec->kebabPlural();
        $nome = $spec->snakePlural();
        $studly = $spec->studly;
        $studlyPlural = $spec->studlyPlural;
        $param = $spec->snake();

        if (str_contains($conteudo, "Route::prefix('{$prefixo}')")) {
            $this->pulados[] = 'routes/admin.php (grupo de rotas já existe)';

            return;
        }

        $marcador = '    // Adicione aqui as rotas do seu módulo de negócio';

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente('routes/admin.php', $marcador, 'das rotas');

            return;
        }

        $bloco = <<<PHP
    Route::prefix('{$prefixo}')->name('{$nome}.')->group(function (): void {
        Route::get('/', App\\Livewire\\Admin\\{$studlyPlural}\\Index{$studly}::class)->name('index');
        Route::get('/criar', App\\Livewire\\Admin\\{$studlyPlural}\\Form{$studly}::class)->name('create');
        Route::get('/{{$param}}/editar', App\\Livewire\\Admin\\{$studlyPlural}\\Form{$studly}::class)->name('edit');
    });

{$marcador}
PHP;

        $conteudo = str_replace($marcador, $bloco, $conteudo);
        File::put($arquivo, $conteudo);

        $this->criados[] = 'routes/admin.php (grupo de rotas)';
    }

    private function injetarPermissoes(EspecificacaoModulo $spec): void
    {
        $arquivo = base_path('config/access.php');
        $conteudo = (string) File::get($arquivo);

        $base = $spec->snakePlural();

        if (str_contains($conteudo, "'{$base}.listar'")) {
            $this->pulados[] = 'config/access.php (permissões já existem)';

            return;
        }

        $marcador = '            ' . self::MARCADOR_PERMISSOES;

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente('config/access.php', $marcador, 'das permissões');
            $this->line($this->snippetPermissoes($spec));

            return;
        }

        $linhas = [];
        foreach ($spec->permissoes() as $perm => $meta) {
            $linhas[] = "            '{$perm}' => [";
            $linhas[] = "                'label' => '{$meta['label']}',";
            $linhas[] = "                'descricao' => '{$meta['descricao']}',";
            $linhas[] = '            ],';
        }

        $bloco = implode("\n", $linhas) . "\n" . $marcador;
        $conteudo = str_replace($marcador, $bloco, $conteudo);
        File::put($arquivo, $conteudo);

        $this->criados[] = 'config/access.php (permissões)';
    }

    private function snippetPermissoes(EspecificacaoModulo $spec): string
    {
        $linhas = [];
        foreach ($spec->permissoes() as $perm => $meta) {
            $linhas[] = "    '{$perm}' => ['label' => '{$meta['label']}', 'descricao' => '{$meta['descricao']}'],";
        }

        return implode("\n", $linhas);
    }

    /**
     * Injeta o item de menu do módulo na seção "Negócio" de config/admin-menu.php
     * (mesma técnica de âncora + str_replace). `permission => '{modulo}.listar'`
     * deixa o item visível só para super-admin até a permissão ser atribuída.
     */
    private function injetarMenu(EspecificacaoModulo $spec): void
    {
        if ($this->option('skip-menu')) {
            return;
        }

        $arquivo = base_path('config/admin-menu.php');
        $conteudo = (string) File::get($arquivo);

        $base = $spec->snakePlural();

        if (str_contains($conteudo, "'key' => '{$base}'")) {
            $this->pulados[] = 'config/admin-menu.php (item de menu já existe)';

            return;
        }

        $marcador = '            ' . self::MARCADOR_MENU;

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente('config/admin-menu.php', $marcador, 'do menu');
            $this->line($this->snippetMenu($spec));

            return;
        }

        $label = (string) ($this->option('menu') ?: $spec->studlyPlural);
        $icon = (string) $this->option('menu-icon');

        $bloco = implode("\n", [
            '            [',
            "                'key' => '{$base}',",
            "                'label' => '{$label}',",
            "                'icon' => '{$icon}',",
            "                'route' => 'admin.{$base}.index',",
            "                'permission' => '{$base}.listar',",
            "                'active' => ['admin.{$base}.*'],",
            '            ],',
            $marcador,
        ]);

        $conteudo = str_replace($marcador, $bloco, $conteudo);
        File::put($arquivo, $conteudo);

        $this->criados[] = 'config/admin-menu.php (item de menu, seção Negócio)';
    }

    private function snippetMenu(EspecificacaoModulo $spec): string
    {
        $base = $spec->snakePlural();
        $label = (string) ($this->option('menu') ?: $spec->studlyPlural);
        $icon = (string) $this->option('menu-icon');

        return "    ['key' => '{$base}', 'label' => '{$label}', 'icon' => '{$icon}', 'route' => 'admin.{$base}.index', 'permission' => '{$base}.listar', 'active' => ['admin.{$base}.*']],";
    }

    /**
     * Mapa stub => caminho dentro do pacote (src/, database/, resources/, tests/).
     *
     * @return array<string, string>
     */
    private function mapaArquivosPacote(EspecificacaoModulo $spec): array
    {
        $pkg = $spec->pacote;
        if ($pkg === null) {
            return [];
        }

        $dir = $pkg->dir;
        $studly = $spec->studly;
        $studlyPlural = $spec->studlyPlural;
        $snake = $spec->snake();
        $snakePlural = $spec->snakePlural();
        $migracao = now()->format('Y_m_d_His');

        // Com soft-delete, o _acoes vem do stub com a estrutura de lixeira.

        $mapa = [
            'migration.stub' => "{$dir}/database/migrations/{$migracao}_create_{$spec->tabela()}_table.php",
            'factory.stub' => "{$dir}/database/factories/{$studly}Factory.php",
            'model.stub' => "{$dir}/src/Models/{$studly}.php",
            'enum.stub' => "{$dir}/src/Enums/{$spec->statusEnumShort()}.php",
            'dto.stub' => "{$dir}/src/DTOs/{$studly}DTO.php",
            'rules.stub' => "{$dir}/src/Http/Requests/{$studly}Rules.php",
            'store-request.stub' => "{$dir}/src/Http/Requests/Store{$studly}Request.php",
            'update-request.stub' => "{$dir}/src/Http/Requests/Update{$studly}Request.php",
            'create-action.stub' => "{$dir}/src/Actions/Create{$studly}Action.php",
            'update-action.stub' => "{$dir}/src/Actions/Update{$studly}Action.php",
            'service.stub' => "{$dir}/src/Services/{$studly}Service.php",
            'policy.stub' => "{$dir}/src/Policies/{$studly}Policy.php",
            'livewire-index.stub' => "{$dir}/src/Livewire/{$studlyPlural}/Index{$studly}.php",
            'livewire-form.stub' => "{$dir}/src/Livewire/{$studlyPlural}/Form{$studly}.php",
            'livewire-table.stub' => "{$dir}/src/Livewire/{$studlyPlural}/{$studly}Table.php",
            'view-index.stub' => "{$dir}/resources/views/livewire/{$snakePlural}/index-{$snakePlural}.blade.php",
            'view-form.stub' => "{$dir}/resources/views/livewire/{$snakePlural}/form-{$snake}.blade.php",
            'view-ficha.stub' => "{$dir}/resources/views/livewire/{$snakePlural}/_ficha.blade.php",
            'test.stub' => "{$dir}/tests/Feature/{$studlyPlural}/{$studly}CrudTest.php",
        ];

        return $mapa;
    }

    /**
     * Integra o CRUD ao pacote sem editar o core: rotas no routes/admin.php do
     * pacote, permissões/menu no config/{slug}.php, componentes/policy no provider.
     */
    private function integrarNoPacote(EspecificacaoModulo $spec): void
    {
        $this->injetarRotasPacote($spec);
        $this->injetarConfigPacote($spec);
        $this->registrarNoProviderPacote($spec);
    }

    private function injetarRotasPacote(EspecificacaoModulo $spec): void
    {
        $pkg = $spec->pacote;
        if ($pkg === null) {
            return;
        }

        $arquivo = base_path("{$pkg->dir}/routes/admin.php");
        $conteudo = (string) File::get($arquivo);
        $nome = $spec->rotaNome();

        if (str_contains($conteudo, "->name('{$nome}.')")) {
            $this->pulados[] = "{$pkg->dir}/routes/admin.php (rotas já existem)";

            return;
        }

        $prefixoUrl = $pkg->slug . '/' . $spec->kebabPlural();
        $param = $spec->snake();
        $index = '\\' . $spec->nsLivewire() . '\\Index' . $spec->studly;
        $form = '\\' . $spec->nsLivewire() . '\\Form' . $spec->studly;
        $marcador = self::MARCADOR_ROTAS;

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente("{$pkg->dir}/routes/admin.php", $marcador, 'das rotas');

            return;
        }

        $bloco = implode("\n", [
            $marcador,
            "\\Illuminate\\Support\\Facades\\Route::prefix('{$prefixoUrl}')->name('{$nome}.')->group(function (): void {",
            "    \\Illuminate\\Support\\Facades\\Route::get('/', {$index}::class)->name('index');",
            "    \\Illuminate\\Support\\Facades\\Route::get('/criar', {$form}::class)->name('create');",
            "    \\Illuminate\\Support\\Facades\\Route::get('/{{$param}}/editar', {$form}::class)->name('edit');",
            '});',
        ]);

        File::put($arquivo, str_replace($marcador, $bloco, $conteudo));
        $this->criados[] = "{$pkg->dir}/routes/admin.php (rotas)";
        $this->escritos[] = "{$pkg->dir}/routes/admin.php";
    }

    private function injetarConfigPacote(EspecificacaoModulo $spec): void
    {
        $pkg = $spec->pacote;
        if ($pkg === null) {
            return;
        }

        $arquivo = base_path("{$pkg->dir}/config/{$pkg->slug}.php");
        $conteudo = $original = (string) File::get($arquivo);
        $chave = $spec->snakePlural();

        // A injeção depende de um comentário-marcador no config do pacote.
        // Quando ele falta — e faltava em 2 dos 3 pacotes — o bloco era pulado,
        // o arquivo era reescrito idêntico e o comando reportava "criado" assim
        // mesmo. Resultado: 19 arquivos, uma rota, e uma tela INALCANÇÁVEL, sem
        // item de menu e com o gate negando porque a permissão nunca chegou ao
        // catálogo. Falha silenciosa.
        $marcador = '        ' . self::MARCADOR_RECURSOS;

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente("{$pkg->dir}/config/{$pkg->slug}.php", $marcador, 'dos recursos');

            return;
        }

        if (str_contains($conteudo, "'{$chave}' => [")) {
            return;
        }

        // UMA entrada, de onde saem seis permissões, o item de menu, o nome da
        // rota, a permissão que guarda o item e o padrão de `active`. Antes eram
        // dois blocos escritos por extenso — vinte linhas de permissão e sete de
        // menu — e cada linha era uma chance de divergir da fórmula do catálogo.
        $linhas = [
            "        '{$chave}' => [",
            sprintf("            'label' => '%s',", $this->option('menu') ?: $spec->studlyPlural),
            sprintf("            'singular' => '%s',", mb_strtolower($spec->studly)),
            sprintf("            'icone' => '%s',", $this->option('menu-icon')),
        ];

        if ($this->option('sem-soft-delete')) {
            $linhas[] = "            'sem_lixeira' => true,";
        }

        // --skip-menu era ignorado em modo pacote: o item entrava de qualquer
        // jeito, e quem pediu para não ter menu descobria depois, na tela.
        if ($this->option('skip-menu')) {
            $linhas[] = "            'sem_menu' => true,";
        }

        $linhas[] = '        ],';

        $conteudo = str_replace($marcador, implode("\n", $linhas) . "\n" . $marcador, $conteudo);

        if ($conteudo === $original) {
            // Nada mudou. Anunciar "criado" seria mentira — e mentira verde é
            // pior que erro vermelho.
            return;
        }

        File::put($arquivo, $conteudo);
        $this->criados[] = sprintf('%s/config/%s.php (recurso %s)', $pkg->dir, $pkg->slug, $chave);
        $this->escritos[] = sprintf('%s/config/%s.php', $pkg->dir, $pkg->slug);
    }

    private function registrarNoProviderPacote(EspecificacaoModulo $spec): void
    {
        $pkg = $spec->pacote;
        if ($pkg === null) {
            return;
        }

        $arquivo = base_path("{$pkg->dir}/src/{$pkg->providerClass}.php");
        $conteudo = (string) File::get($arquivo);
        $nsLw = '\\' . $spec->nsLivewire();
        $table = $nsLw . '\\' . $spec->studly . 'Table';

        $marcador = '        ' . self::MARCADOR_PROVIDER;

        if (str_contains($conteudo, "{$table}::class")) {
            $this->pulados[] = "{$pkg->dir}/src/{$pkg->providerClass}.php (componentes já registrados)";

            return;
        }

        if (! str_contains($conteudo, $marcador)) {
            $this->marcadorAusente(
                "{$pkg->dir}/src/{$pkg->providerClass}.php",
                $marcador,
                'dos componentes Livewire',
            );

            return;
        }

        $model = '\\' . $spec->nsModels() . '\\' . $spec->studly;
        $policy = '\\' . $spec->nsPolicies() . '\\' . $spec->studly . 'Policy';
        $nome = $spec->rotaNome();

        $linhas = [
            "        \\Livewire\\Livewire::component('{$nome}.index', {$nsLw}\\Index{$spec->studly}::class);",
            "        \\Livewire\\Livewire::component('{$nome}.form', {$nsLw}\\Form{$spec->studly}::class);",
            "        \\Livewire\\Livewire::component('{$spec->lwTag()}', {$table}::class);",
            "        \\Illuminate\\Support\\Facades\\Gate::policy({$model}::class, {$policy}::class);",
            $marcador,
        ];

        File::put($arquivo, str_replace($marcador, implode("\n", $linhas), $conteudo));
        $this->criados[] = "{$pkg->dir}/src/{$pkg->providerClass}.php (componentes + policy)";
        $this->escritos[] = "{$pkg->dir}/src/{$pkg->providerClass}.php";
    }

    /** Onde as views do recurso foram escritas — no pacote ou no produto. */
    private function dirDeViews(EspecificacaoModulo $spec): string
    {
        $pkg = $spec->pacote;

        return $pkg === null
            ? 'resources/views/livewire/admin/' . $spec->snakePlural() . '/'
            : "{$pkg->dir}/resources/views/livewire/" . $spec->snakePlural() . '/';
    }

    private function resumo(EspecificacaoModulo $spec): void
    {
        $this->newLine();
        $this->info("Recurso {$spec->studly} gerado.");

        foreach ($this->criados as $c) {
            $this->line("  <fg=green>criado</>  {$c}");
        }
        foreach ($this->pulados as $p) {
            $this->line("  <fg=yellow>pulado</>  {$p}");
        }

        if ($this->naoLigados !== []) {
            $this->newLine();
            $this->error('Os arquivos foram gerados, mas a tela NÃO está ligada:');

            foreach ($this->naoLigados as $n) {
                $this->line("  <fg=red>faltou</>  {$n}");
            }

            $this->line('  Sem isso a tela não tem rota, ou não tem componente registrado,');
            $this->line('  ou não tem permissão no catálogo — e abre em 404 ou em 403.');
        }

        $this->newLine();
        $this->line('<options=bold>Próximos passos:</>');
        $this->line($this->formatado === true
            ? '  1. <fg=green>Pint já passou nos arquivos gerados.</> Falta o Prettier nas views: npx prettier --write ' . $this->dirDeViews($spec)
            : '  1. Formate a saída: ./vendor/bin/pint && npx prettier --write ' . $this->dirDeViews($spec));
        $this->line('  2. Revise a migration e os campos gerados.');
        $this->line('  3. php artisan migrate');
        $this->line('  4. php artisan access:sync   (publica as permissões do módulo)');
        $this->line('  5. Atribua as permissões aos perfis desejados (o item de menu já aparece para super-admin).');
        $this->line("  6. Acesse /admin/{$spec->kebabPlural()}.");
        $this->newLine();
    }
}
