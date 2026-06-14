<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Generator\CampoModulo;
use App\Support\Generator\EspecificacaoModulo;
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
        {--soft-delete : Reservado para evolução futura}
        {--force : Sobrescreve arquivos existentes}';

    protected $description = 'Gera um módulo CRUD completo no padrão do projeto (model, DTO, actions, Livewire, PowerGrid, views, teste, rotas e permissões).';

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
        $spec = new EspecificacaoModulo($nome, $campos, tenant: (bool) $this->option('tenant'));

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

        $this->injetarRotas($spec);
        $this->injetarPermissoes($spec);

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

        return [
            ...$spec->tokens(),
            '__MIGRATION_COLUNAS__' => $spec->migrationColunas(),
            '__MIGRATION_INDICES__' => $spec->migrationIndices(),
            '__MODEL_FILLABLE__' => $spec->modelFillable(),
            '__MODEL_CASTS__' => $spec->modelCasts(),
            '__DTO_PROPS__' => $spec->dtoProps(),
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
            '__FORM_VIEW_FIELDS__' => $spec->formViewFields(),
            '__ENUM_CASES__' => $spec->enumCases(),
            '__ENUM_LABEL_ARMS__' => $spec->enumLabelArms(),
            '__ENUM_VARIANT_ARMS__' => $spec->enumVariantArms(),
            '__TEST_TENANT_SETUP__' => $spec->testTenantSetup(),
            '__TEST_SET_CAMPOS__' => $spec->testSets(),
        ];
    }

    /**
     * Mapa stub => caminho de destino (relativo à raiz do projeto).
     *
     * @return array<string, string>
     */
    private function mapaArquivos(EspecificacaoModulo $spec): array
    {
        $studly = $spec->studly;
        $studlyPlural = $spec->studlyPlural;
        $snake = $spec->snake();
        $snakePlural = $spec->snakePlural();
        $migracao = now()->format('Y_m_d_His');

        return [
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
            'view-acoes.stub' => "resources/views/livewire/admin/{$snakePlural}/_acoes.blade.php",
            'test.stub' => "tests/Feature/Admin/{$studlyPlural}/{$studly}CrudTest.php",
        ];
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
        $this->line('  5. Atribua as permissões aos perfis desejados (ou use super-admin).');
        $this->line("  6. Adicione o item ao menu lateral apontando para route('admin.{$spec->snakePlural()}.index').");
        $this->line("  7. Acesse /admin/{$spec->kebabPlural()}.");
        $this->newLine();
    }
}
