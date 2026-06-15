<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Generator\CampoModulo;
use App\Support\Generator\EspecificacaoModulo;
use App\Support\Generator\ModuloPacote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Gera um módulo CRUD completo já no padrão do projeto (Migration, Factory,
 * Model, Enum de status, DTO, FormRequests + Rules, Actions, Service, Policy,
 * Livewire Index/Form/Table, views e teste Feature), além de injetar as rotas
 * e as permissões no catálogo. Objetivo: o dev só preenche a regra de negócio.
 *
 *   php artisan make:modulo Produto \
 *     --fields="nome:string, sku:string:unique, preco:money, descricao:text:nullable, status:enum(rascunho|publicado|arquivado)" \
 *     --tenant
 *
 * Tipos: string, text, integer, money, boolean, date, datetime, email, cnpj,
 * cpf, cep, phone, enum(a|b|c). Modificadores: nullable, unique.
 */
final class MakeModuloCommand extends Command
{
    protected $signature = 'make:modulo
        {nome : Nome do módulo no singular, PascalCase (ex.: Produto)}
        {--fields= : Lista "nome:tipo:modificador" separada por vírgula}
        {--tenant : Vincula o módulo à empresa ativa (trait BelongsToEmpresa)}
        {--module= : Gera dentro de um módulo-pacote existente (ex.: Rh)}
        {--menu= : Rótulo do item de menu (default: nome no plural)}
        {--menu-icon=tabler--folder : Ícone do item de menu (Tabler)}
        {--skip-menu : Não injeta o item no menu lateral (config/admin-menu.php)}
        {--sem-soft-delete : Desativa o soft-delete (por padrão registros são recuperáveis via deleted_at)}
        {--force : Sobrescreve arquivos existentes}';

    protected $description = 'Gera um módulo CRUD completo no padrão do projeto (model, DTO, actions, Livewire, PowerGrid, views, teste, rotas, permissões e item de menu).';

    /** @var list<string> caminhos gerados (para o resumo final) */
    private array $criados = [];

    /** @var list<string> caminhos pulados por já existirem */
    private array $pulados = [];

    public function handle(): int
    {
        $nome = Str::studly(Str::singular((string) $this->argument('nome')));

        if ($nome === '') {
            $this->error('Informe um nome de módulo válido (ex.: Produto).');

            return self::FAILURE;
        }

        $campos = $this->parseFields((string) $this->option('fields'));

        $pacote = null;
        $module = (string) $this->option('module');
        if ($module !== '') {
            $pacote = ModuloPacote::paraNome($module);
            if (! File::isDirectory(base_path($pacote->dir))) {
                $this->error("Módulo-pacote {$pacote->pacote} não encontrado em {$pacote->dir}.");
                $this->line("Crie a casca primeiro:  php artisan make:modulo-pacote {$pacote->studly}");

                return self::FAILURE;
            }
        }

        $spec = new EspecificacaoModulo($nome, $campos, tenant: (bool) $this->option('tenant'), pacote: $pacote, softDelete: ! (bool) $this->option('sem-soft-delete'));

        $stubDir = base_path('stubs/modulo');
        if (! File::isDirectory($stubDir)) {
            $this->error("Stubs não encontrados em {$stubDir}. Eles fazem parte do boilerplate.");

            return self::FAILURE;
        }

        $repl = $this->montarReplacements($spec);
        $arquivos = $this->mapaArquivos($spec);
        $this->resolverMigrationExistente($spec, $arquivos);

        foreach ($arquivos as $stub => $destino) {
            $this->gerarArquivo($stubDir, $stub, $destino, $repl);
        }

        if ($spec->pacote !== null) {
            $this->integrarNoPacote($spec);
        } else {
            $this->injetarRotas($spec);
            $this->injetarPermissoes($spec);
            $this->injetarMenu($spec);
        }

        $this->resumo($spec);

        return self::SUCCESS;
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
            ? "\$html = static fn (string \$chave): ?string => isset(\$data[\$chave]) && trim((string) \$data[\$chave]) !== ''\n            ? \\App\\Support\\Html\\HtmlSanitizer::clean((string) \$data[\$chave])\n            : null;\n\n        "
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
            '__PG_FIELDS__' => $spec->pgFields(),
            '__PG_COLUMNS__' => $spec->pgColumns(),
            '__PG_FILTERS__' => $spec->pgFilters(),
            '__FORM_BODY__' => $spec->formBody(),
            '__ENUM_CASES__' => $spec->enumCases(),
            '__ENUM_LABEL_ARMS__' => $spec->enumLabelArms(),
            '__ENUM_VARIANT_ARMS__' => $spec->enumVariantArms(),
            '__TEST_TENANT_SETUP__' => $spec->testTenantSetup(),
            '__TEST_SET_CAMPOS__' => $spec->testSets(),
            '__PDF_COLUNAS__' => $spec->pdfColunas(),
            '__PDF_MAP_ROW__' => $spec->pdfMapRow(),
            '__MODEL_CLASS_LIXEIRA__' => $spec->metodoModelClassLixeira(),
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
        $acoesStub = $spec->softDelete ? 'view-acoes-lixeira.stub' : 'view-acoes.stub';

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
            $acoesStub => "resources/views/livewire/admin/{$snakePlural}/_acoes.blade.php",
            'view-export-pdf.stub' => "resources/views/livewire/admin/{$snakePlural}/_export-pdf.blade.php",
            'test.stub' => "tests/Feature/Admin/{$studlyPlural}/{$studly}CrudTest.php",
        ];

        if ($spec->softDelete) {
            $mapa['view-lixeira-toggle.stub'] = "resources/views/livewire/admin/{$snakePlural}/_lixeira-toggle.blade.php";
        }

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
    private function gerarArquivo(string $stubDir, string $stub, string $destinoRelativo, array $repl): void
    {
        $destino = base_path($destinoRelativo);

        if (File::exists($destino) && ! $this->option('force')) {
            $this->pulados[] = $destinoRelativo;

            return;
        }

        $conteudo = (string) File::get("{$stubDir}/{$stub}");
        $conteudo = strtr($conteudo, $repl);

        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $conteudo);

        $this->criados[] = $destinoRelativo;
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

        $marcador = '            // make:modulo insere permissões de negócio acima desta linha';

        if (! str_contains($conteudo, $marcador)) {
            $this->warn('Âncora de permissões ausente em config/access.php — registre manualmente:');
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

        $marcador = '            // make:modulo insere itens de menu acima desta linha';

        if (! str_contains($conteudo, $marcador)) {
            $this->warn("Seção 'negocio'/âncora ausente em config/admin-menu.php — adicione o item manualmente:");
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
        $acoesStub = $spec->softDelete ? 'view-acoes-lixeira.stub' : 'view-acoes.stub';

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
            $acoesStub => "{$dir}/resources/views/livewire/{$snakePlural}/_acoes.blade.php",
            'view-export-pdf.stub' => "{$dir}/resources/views/livewire/{$snakePlural}/_export-pdf.blade.php",
            'test.stub' => "{$dir}/tests/Feature/{$studlyPlural}/{$studly}CrudTest.php",
        ];

        if ($spec->softDelete) {
            $mapa['view-lixeira-toggle.stub'] = "{$dir}/resources/views/livewire/{$snakePlural}/_lixeira-toggle.blade.php";
        }

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
        $marcador = '// make:modulo insere as rotas do módulo abaixo desta linha';

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
    }

    private function injetarConfigPacote(EspecificacaoModulo $spec): void
    {
        $pkg = $spec->pacote;
        if ($pkg === null) {
            return;
        }

        $arquivo = base_path("{$pkg->dir}/config/{$pkg->slug}.php");
        $conteudo = (string) File::get($arquivo);
        $base = $spec->permissaoBase();

        $marcadorPerm = '        // make:modulo insere as permissões do módulo acima desta linha';
        if (! str_contains($conteudo, "'{$base}.listar'") && str_contains($conteudo, $marcadorPerm)) {
            $linhas = [];
            foreach ($spec->permissoes() as $perm => $meta) {
                $linhas[] = "        '{$perm}' => ['label' => '{$meta['label']}', 'descricao' => '{$meta['descricao']}'],";
            }
            $conteudo = str_replace($marcadorPerm, implode("\n", $linhas) . "\n" . $marcadorPerm, $conteudo);
        }

        $key = $pkg->slug . '-' . $spec->snakePlural();
        $marcadorMenu = '        // make:modulo insere os itens de menu do módulo acima desta linha';
        if (! str_contains($conteudo, "'key' => '{$key}'") && str_contains($conteudo, $marcadorMenu)) {
            $label = (string) ($this->option('menu') ?: $spec->studlyPlural);
            $icon = (string) $this->option('menu-icon');
            $bloco = implode("\n", [
                '        [',
                "            'key' => '{$key}',",
                "            'label' => '{$label}',",
                "            'icon' => '{$icon}',",
                "            'route' => 'admin.{$base}.index',",
                "            'permission' => '{$base}.listar',",
                "            'active' => ['admin.{$base}.*'],",
                '        ],',
                $marcadorMenu,
            ]);
            $conteudo = str_replace($marcadorMenu, $bloco, $conteudo);
        }

        File::put($arquivo, $conteudo);
        $this->criados[] = "{$pkg->dir}/config/{$pkg->slug}.php (permissões + menu)";
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

        $marcador = '        // make:modulo registra os componentes Livewire e as policies do módulo acima desta linha';
        if (str_contains($conteudo, "{$table}::class") || ! str_contains($conteudo, $marcador)) {
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
    }

    private function resumo(EspecificacaoModulo $spec): void
    {
        $this->newLine();
        $this->info("Módulo {$spec->studly} gerado.");

        foreach ($this->criados as $c) {
            $this->line("  <fg=green>criado</>  {$c}");
        }
        foreach ($this->pulados as $p) {
            $this->line("  <fg=yellow>pulado</>  {$p}");
        }

        $this->newLine();
        $this->line('<options=bold>Próximos passos:</>');
        $this->line('  1. Formate a saída: ./vendor/bin/pint && npx prettier --write resources/views/livewire/admin/' . $spec->snakePlural() . '/');
        $this->line('  2. Revise a migration e os campos gerados.');
        $this->line('  3. php artisan migrate');
        $this->line('  4. php artisan access:sync   (publica as permissões do módulo)');
        $this->line('  5. Atribua as permissões aos perfis desejados (o item de menu já aparece para super-admin).');
        $this->line("  6. Acesse /admin/{$spec->kebabPlural()}.");
        $this->newLine();
    }
}
