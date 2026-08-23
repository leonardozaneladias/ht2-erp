<?php

declare(strict_types=1);

namespace App\Support\Generator;

use Illuminate\Support\Str;

/**
 * Spec resolvida de um módulo a gerar: nomes derivados + campos + flags, e os
 * "blocos" de código que o gerador injeta nos stubs.
 *
 * Tokens usados nos stubs seguem o padrão `__UPPER_SNAKE__` (não colidem com
 * Blade `{{ }}` nem com sintaxe PHP). Os escalares vêm de tokens(); os trechos
 * multi-linha (colunas, casts, regras, campos do form, etc.) vêm dos métodos
 * `bloco*()`. Indentação é "boa o suficiente" — o gerador roda Pint/Prettier
 * sobre a saída.
 */
final class EspecificacaoModulo
{
    /** @var list<CampoModulo> campos não-status (status é tratado à parte) */
    public readonly array $campos;

    public readonly CampoModulo $status;

    public readonly string $studly;

    public readonly string $studlyPlural;

    /**
     * @param  list<CampoModulo>  $todosCampos
     */
    public function __construct(
        string $nome,
        array $todosCampos,
        public readonly bool $tenant = false,
        public readonly ?Extensao $pacote = null,
        public readonly bool $softDelete = false,
    ) {
        $this->studly = Str::studly(Str::singular($nome));
        $this->studlyPlural = Str::plural($this->studly);

        $status = null;
        $regulares = [];

        foreach ($todosCampos as $campo) {
            if ($campo->ehStatus()) {
                $status = $campo;

                continue;
            }
            $regulares[] = $campo;
        }

        // Todo módulo nasce com um Enum de status backed (§5.4). Default:
        // ativo|inativo, caso a spec não declare um `status:enum(...)`.
        $this->status = $status ?? new CampoModulo('status', 'enum', enumValores: ['ativo', 'inativo']);
        $this->campos = $regulares;
    }

    // ---- Nomes derivados --------------------------------------------------

    public function snake(): string
    {
        return Str::snake($this->studly);
    }

    public function snakePlural(): string
    {
        return Str::snake($this->studlyPlural);
    }

    public function camel(): string
    {
        return Str::camel($this->studly);
    }

    public function kebabPlural(): string
    {
        return Str::kebab($this->studlyPlural);
    }

    public function tabela(): string
    {
        return $this->snakePlural();
    }

    public function statusEnumShort(): string
    {
        return 'Status' . $this->studly;
    }

    public function statusCaseDefault(): string
    {
        return Str::studly($this->status->enumValores[0] ?? 'ativo');
    }

    public function statusValueDefault(): string
    {
        return $this->status->enumValores[0] ?? 'ativo';
    }

    public function nsModels(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Models' : 'App\\Models';
    }

    public function nsEnums(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Enums' : 'App\\Enums';
    }

    public function nsDtos(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\DTOs' : 'App\\DTOs\\Admin';
    }

    public function nsActions(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Actions' : 'App\\Actions\\Admin';
    }

    public function nsServices(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Services' : 'App\\Services\\Admin';
    }

    public function nsPolicies(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Policies' : 'App\\Policies';
    }

    public function nsRequests(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Http\\Requests' : 'App\\Http\\Requests\\Admin';
    }

    public function nsLivewire(): string
    {
        return $this->pacote !== null
            ? $this->nsBase() . '\\Livewire\\' . $this->studlyPlural
            : 'App\\Livewire\\Admin\\' . $this->studlyPlural;
    }

    public function nsFactories(): string
    {
        return $this->pacote !== null ? $this->nsBase() . '\\Database\\Factories' : 'Database\\Factories';
    }

    public function viewPrefix(): string
    {
        return $this->pacote !== null
            ? $this->pacote->viewNamespace . '::livewire.' . $this->snakePlural()
            : 'livewire.admin.' . $this->snakePlural();
    }

    public function lwTag(): string
    {
        $tabela = $this->kebabPlural() . '.' . Str::kebab($this->studly) . '-table';

        return $this->pacote !== null ? $this->pacote->viewNamespace . '.' . $tabela : 'admin.' . $tabela;
    }

    public function rotaNome(): string
    {
        return $this->pacote !== null ? $this->pacote->slug . '.' . $this->snakePlural() : $this->snakePlural();
    }

    public function permissaoBase(): string
    {
        return $this->pacote !== null ? $this->pacote->slug . '.' . $this->snakePlural() : $this->snakePlural();
    }

    public function temToggleStatus(): bool
    {
        $valores = array_map('strtolower', $this->status->enumValores);
        sort($valores);

        return $valores === ['ativo', 'inativo'];
    }

    /**
     * Lista de permissões do módulo (módulo.acao) que vão ao catálogo.
     *
     * @return array<string, array{label: string, descricao: string}>
     */
    public function permissoes(): array
    {
        $base = $this->permissaoBase();
        $singular = mb_strtolower($this->studly);
        $plural = mb_strtolower($this->studlyPlural);

        $permissoes = [
            "{$base}.listar" => [
                'label' => "Listar {$plural}",
                'descricao' => "Ver a listagem de {$plural}.",
            ],
            "{$base}.criar" => [
                'label' => "Criar {$plural}",
                'descricao' => "Cadastrar novos registros de {$singular}.",
            ],
            "{$base}.editar" => [
                'label' => "Editar {$plural}",
                'descricao' => "Alterar dados e status de {$plural}.",
            ],
            "{$base}.deletar" => [
                'label' => "Excluir {$plural}",
                'descricao' => $this->softDelete ? "Mover {$plural} para a lixeira." : "Remover {$plural}.",
            ],
        ];

        // Soft-delete: a exclusão vai para a lixeira; restaurar e excluir
        // definitivamente (force-delete) ganham permissões próprias.
        if ($this->softDelete) {
            $permissoes["{$base}.restaurar"] = [
                'label' => "Restaurar {$plural}",
                'descricao' => "Restaurar {$plural} da lixeira.",
            ];
            $permissoes["{$base}.excluir_permanente"] = [
                'label' => "Excluir {$plural} permanentemente",
                'descricao' => "Remover {$plural} definitivamente do banco (irreversível).",
            ];
        }

        return $permissoes;
    }

    // ---- Tokens escalares -------------------------------------------------

    /**
     * @return array<string, string>
     */
    public function tokens(): array
    {
        return [
            '__MODULO_STUDLY__' => $this->studly,
            '__MODULO_STUDLY_PLURAL__' => $this->studlyPlural,
            '__MODULO_CAMEL__' => $this->camel(),
            '__MODULO_SNAKE__' => $this->snake(),
            '__MODULO_SNAKE_PLURAL__' => $this->snakePlural(),
            '__MODULO_KEBAB__' => Str::kebab($this->studly),
            '__MODULO_KEBAB_PLURAL__' => $this->kebabPlural(),
            '__MODULO_LABEL__' => $this->studly,
            '__MODULO_LABEL_PLURAL__' => $this->studlyPlural,
            '__MODULO_TABELA__' => $this->tabela(),
            '__MODULO_ROTA_PREFIXO__' => $this->kebabPlural(),
            '__MODULO_ROTA_NOME__' => $this->rotaNome(),
            '__MODULO_PARAM__' => $this->snake(),
            '__STATUS_ENUM__' => $this->statusEnumShort(),
            '__STATUS_CASE_DEFAULT__' => $this->statusCaseDefault(),
            '__STATUS_VALUE_DEFAULT__' => $this->statusValueDefault(),
            '__NS_MODELS__' => $this->nsModels(),
            '__NS_ENUMS__' => $this->nsEnums(),
            '__NS_DTOS__' => $this->nsDtos(),
            '__NS_ACTIONS__' => $this->nsActions(),
            '__NS_SERVICES__' => $this->nsServices(),
            '__NS_POLICIES__' => $this->nsPolicies(),
            '__NS_REQUESTS__' => $this->nsRequests(),
            '__NS_LIVEWIRE__' => $this->nsLivewire(),
            '__NS_FACTORIES__' => $this->nsFactories(),
            '__VIEW_PREFIX__' => $this->viewPrefix(),
            '__LW_TAG__' => $this->lwTag(),
            // Soft-delete: tokens com quebra embutida (nada quando ausente, p/ não deixar linha vazia).
            '__MIGRATION_SOFT_DELETE__' => $this->softDelete ? "\n            \$table->softDeletes();" : '',
            '__MODEL_USE_SOFT_DELETE__' => $this->softDelete ? "\nuse Illuminate\\Database\\Eloquent\\SoftDeletes;" : '',
            '__MODEL_TRAIT_SOFT_DELETE__' => $this->softDelete ? "\n    use SoftDeletes;" : '',
            '__MODEL_USE_TENANT__' => $this->tenant ? 'use App\Models\Concerns\BelongsToEmpresa;' : '',
            '__MODEL_TRAIT_TENANT__' => $this->tenant ? 'use BelongsToEmpresa;' : '',
            // Filtro multi-empresa nas listagens (só faz sentido em módulos tenant).
            '__USE_MULTI_EMPRESA__' => $this->tenant ? 'use App\Livewire\Concerns\FiltraPorMultiEmpresa;' : '',
            '__TRAIT_MULTI_EMPRESA__' => $this->tenant ? 'use FiltraPorMultiEmpresa;' : '',
            '__PERMISSAO_LISTAGEM__' => $this->tenant ? $this->metodoPermissaoListagem() : '',
            '__DS_OPEN__' => $this->tenant ? '$this->aplicarEscopoMultiEmpresa(' : '',
            '__DS_CLOSE__' => $this->tenant ? ')' : '',
            '__FIELDS_OPEN__' => $this->tenant ? '$this->camposMultiEmpresa(' : '',
            '__FIELDS_CLOSE__' => $this->tenant ? ')' : '',
            '__COLUNAS_MULTI_EMPRESA__' => $this->tenant ? '...$this->colunasMultiEmpresa(),' : '',
            '__FILTROS_MULTI_EMPRESA__' => $this->tenant ? '...$this->filtrosMultiEmpresa(),' : '',
            '__PDF_LINHA_MULTI_EMPRESA__' => $this->tenant ? '...$this->linhaMultiEmpresa($registro),' : '',
            '__PDF_CABECALHOS_MULTI_EMPRESA__' => $this->tenant ? '...$this->cabecalhosMultiEmpresa(), ' : '',
            // Lixeira (soft-delete): vazios quando !softDelete → saída idêntica à antiga.
            '__USE_COM_LIXEIRA__' => $this->softDelete ? 'use HT2ML\Core\Livewire\Concerns\ComLixeira;' : '',
            '__TRAIT_COM_LIXEIRA__' => $this->softDelete ? 'use ComLixeira;' : '',
            '__DS_LIXEIRA_OPEN__' => $this->softDelete ? '$this->aplicarLixeira(' : '',
            '__DS_LIXEIRA_CLOSE__' => $this->softDelete ? ')' : '',
            // A toolbar do grid (lixeira + exportar PDF) é uma VIEW ÚNICA do core
            // (livewire.admin.partials.lixeira-toolbar) — o gerador não copia mais um
            // `_lixeira-toggle` e um `_export-pdf` por módulo. O que ela precisa saber é
            // o prefixo das permissões, que vem daqui.
            '__PERMISSAO_BASE__' => $this->softDelete
                ? "/** Prefixo das permissões do recurso (ComLixeira). */\n"
                    . "    protected function permissaoBase(): string\n"
                    . "    {\n"
                    . "        return '{$this->permissaoBase()}';\n"
                    . '    }'
                : '',
            '__VERLIXEIRA_PARAM__' => $this->softDelete ? ", 'verLixeira' => \$this->verLixeira" : '',
            '__MODEL_USE_LIXEIRA__' => $this->softDelete ? 'use HT2ML\Core\Models\Contracts\UsaSoftDeletes;' : '',
            '__MODEL_IMPLEMENTS_LIXEIRA__' => $this->softDelete ? ' implements UsaSoftDeletes' : '',
            '__MODEL_DELETED_AT_PROPERTY__' => $this->softDelete ? "\n * @property \Illuminate\Support\Carbon|null \$deleted_at" : '',
        ];
    }

    // ---- Blocos: Migration ------------------------------------------------

    public function migrationColunas(int $espacos = 12): string
    {
        $linhas = [];

        if ($this->tenant) {
            $linhas[] = "\$table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();";
            $linhas[] = '';
        }

        foreach ($this->campos as $campo) {
            $linhas[] = $campo->colunaMigration();
        }

        // status (enum gravado como string)
        $linhas[] = "\$table->string('status')->default('{$this->statusValueDefault()}');";

        return $this->bloco($linhas, $espacos);
    }

    public function migrationIndices(int $espacos = 12): string
    {
        $linhas = [];

        if ($this->tenant) {
            $linhas[] = "\$table->index(['empresa_id', 'status']);";
        } else {
            $linhas[] = "\$table->index('status');";
        }

        foreach ($this->campos as $campo) {
            if ($campo->precisaIndice()) {
                $linhas[] = "\$table->index('{$campo->nome}');";
            }
        }

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Model ----------------------------------------------------

    public function modelFillable(int $espacos = 8): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = "'{$campo->nome}',";
        }
        $linhas[] = "'status',";

        return $this->bloco($linhas, $espacos);
    }

    public function modelCasts(int $espacos = 12): string
    {
        $linhas = ["'status' => {$this->statusEnumShort()}::class,"];

        foreach ($this->campos as $campo) {
            $cast = $campo->castModel();
            if ($cast !== null) {
                $linhas[] = $cast . ',';
            }
        }

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: DTO ------------------------------------------------------

    public function dtoProps(int $espacos = 8): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            if ($campo->tipo === 'multiselect') {
                $linhas[] = '/** @var list<string> */';
            }
            $linhas[] = "public {$campo->tipoPhp()} \${$campo->camel()}{$campo->defaultPhp()},";
        }
        // Default no status garante que nenhum parâmetro obrigatório siga um
        // opcional (deprecação PHP 8.1 / required-after-optional).
        $linhas[] = "public {$this->statusEnumShort()} \$status = {$this->statusEnumShort()}::{$this->statusCaseDefault()},";

        return $this->bloco($linhas, $espacos);
    }

    public function dtoFromArray(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = $this->dtoFromArrayLinha($campo);
        }
        $linhas[] = "status: {$this->statusEnumShort()}::from((string) (\$data['status'] ?? '{$this->statusValueDefault()}')),";

        return $this->bloco($linhas, $espacos);
    }

    public function dtoParaModel(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = "'{$campo->nome}' => \$this->{$campo->camel()},";
        }
        $linhas[] = "'status' => \$this->status,";

        return $this->bloco($linhas, $espacos);
    }

    public function dtoUsaTextoHelper(): bool
    {
        foreach ($this->campos as $campo) {
            if ($campo->nullable && ! in_array($campo->tipo, ['integer', 'money', 'boolean', 'multiselect', 'richtext'], true)) {
                return true;
            }
        }

        return false;
    }

    /** Há richtext nullable? (usa o helper $html com HtmlSanitizer no fromArray). */
    public function dtoUsaHtmlHelper(): bool
    {
        foreach ($this->campos as $campo) {
            if ($campo->tipo === 'richtext' && $campo->nullable) {
                return true;
            }
        }

        return false;
    }

    // ---- Blocos: Livewire form -------------------------------------------

    public function formProps(int $espacos = 4): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            // PHPStan (nível 6) exige tipo de valor em arrays.
            if ($campo->tipo === 'multiselect') {
                $linhas[] = '/** @var list<string> */';
            }
            $linhas[] = "public {$campo->tipoLivewire()} \${$campo->nome} = {$campo->defaultLivewire()};";
        }
        $linhas[] = "public string \$status = '{$this->statusValueDefault()}';";

        return $this->bloco($linhas, $espacos);
    }

    /**
     * Linhas @property (Carbon) para os campos de data — larastan não infere o
     * tipo a partir do cast, então anotamos no docblock do model (token
     * __MODEL_DATE_PROPERTIES__). Vazio quando o módulo não tem datas.
     */
    public function modelDateProperties(): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            if (in_array($campo->tipo, ['date', 'datetime'], true)) {
                $tipo = $campo->nullable ? '\Illuminate\Support\Carbon|null' : '\Illuminate\Support\Carbon';
                $linhas[] = " * @property {$tipo} \${$campo->nome}";
            }
        }

        return $linhas === [] ? '' : "\n" . implode("\n", $linhas);
    }

    public function formMountLoad(int $espacos = 8): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            // RHS por tipo. Datas voltam como Carbon (cast date/datetime) e
            // precisam virar string no formato do input antes de cair na prop.
            $col = "\$registro->{$campo->nome}";
            $rhs = match ($campo->tipo) {
                // MoneyCast devolve o VO — a prop do form guarda centavos (int).
                'money' => $campo->nullable ? "{$col}?->centavos() ?? 0" : "{$col}->centavos()",
                'integer' => "(int) {$col}",
                'boolean' => "(bool) {$col}",
                'multiselect' => "(array) {$col}",
                'date' => $campo->nullable ? "{$col}?->format('Y-m-d')" : "{$col}->format('Y-m-d')",
                'datetime' => $campo->nullable ? "{$col}?->format('Y-m-d H:i')" : "{$col}->format('Y-m-d H:i')",
                default => $campo->nullable ? $col : "(string) {$col}",
            };
            $linhas[] = "\$this->{$campo->nome} = {$rhs};";
        }
        $linhas[] = '$this->status = $registro->status->value;';

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Rules ----------------------------------------------------

    public function regrasBody(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $itens = $campo->regras();
            if ($campo->unique) {
                $itens[] = $this->regraUnique($campo);
            }
            $linhas[] = "'{$campo->nome}' => [" . implode(', ', $itens) . '],';
        }

        $itensStatus = $this->status->regras($this->statusEnumShort());
        $linhas[] = "'status' => [" . implode(', ', $itensStatus) . '],';

        return $this->bloco($linhas, $espacos);
    }

    public function regrasUsaRule(): bool
    {
        if ($this->status->ehEnum()) {
            return true;
        }
        foreach ($this->campos as $campo) {
            if ($campo->usaRuleNaValidacao()) {
                return true;
            }
        }

        return false;
    }

    public function validationAttributes(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = "'{$campo->nome}' => '" . mb_strtolower($campo->label()) . "',";
        }
        $linhas[] = "'status' => 'status',";

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Factory --------------------------------------------------

    public function factoryDefinition(int $espacos = 12): string
    {
        $linhas = [];

        if ($this->tenant) {
            // Usa o tenant ativo quando há contexto (testes/CLI com empresa
            // definida); senão cria uma empresa própria. Evita divergência com
            // o global scope BelongsToEmpresa.
            $linhas[] = "'empresa_id' => app(\App\Support\Tenancy\TenantContext::class)->empresaAtivaId() ?? \App\Models\Empresa::factory(),";
        }

        foreach ($this->campos as $campo) {
            $linhas[] = $campo->fragmentoFactory();
        }
        $linhas[] = "'status' => fake()->randomElement({$this->statusEnumShort()}::cases()),";

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: PowerGrid ------------------------------------------------

    public function pgFields(int $espacos = 12): string
    {
        $linhas = ['->add(\'id\')'];

        foreach ($this->campos as $campo) {
            if (! $campo->ehColunaVisivel()) {
                continue;
            }
            $linhas[] = "->add('{$campo->nome}')";
        }
        $linhas[] = "->add('status_badge', fn ({$this->studly} \$registro): string => \$this->renderStatus(\$registro))";

        return $this->bloco($linhas, $espacos);
    }

    public function pgColumns(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            if (! $campo->ehColunaVisivel()) {
                continue;
            }
            $linhas[] = "Column::make('{$campo->label()}', '{$campo->nome}')";
            $linhas[] = '    ->searchable()';
            $linhas[] = '    ->sortable(),';
            $linhas[] = '';
        }

        $linhas[] = "Column::make('Status', 'status_badge', 'status')";
        $linhas[] = '    ->sortable(),';
        $linhas[] = '';
        $linhas[] = "Column::action('Ações'),";

        return $this->bloco($linhas, $espacos);
    }

    public function pgFilters(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $filtro = $campo->filtroPowerGrid();
            if ($filtro !== null) {
                $linhas[] = $filtro . ',';
            }
        }

        // status: multiSelect com os cases do Enum
        $linhas[] = "Filter::multiSelect('status', 'status')";
        $linhas[] = "    ->dataSource({$this->statusEnumShort()}::options())";
        $linhas[] = "    ->optionValue('value')";
        $linhas[] = "    ->optionLabel('label'),";

        return $this->bloco($linhas, $espacos);
    }

    /**
     * Corpo da ficha de visualização ("Ver"): um field-display por campo,
     * status incluído como badge (token __FICHA_CAMPOS__ do view-ficha.stub).
     */
    public function fichaCampos(int $espacos = 12): string
    {
        $linhas = [];
        $temStatus = false;

        foreach ($this->campos as $campo) {
            $temStatus = $temStatus || $campo->ehStatus();

            foreach ($campo->componenteFicha() as $linha) {
                $linhas[] = $linha;
            }
        }

        // O enum de status é gerado à parte dos --fields (como em pgColumns).
        if (! $temStatus) {
            $linhas[] = '<x-shared.field-display label="Status">';
            $linhas[] = '    <x-shared.badge :variant="$registro->status->variant()" pill size="sm">{{ $registro->status->label() }}</x-shared.badge>';
            $linhas[] = '</x-shared.field-display>';
        }

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Export PDF ----------------------------------------------

    public function pdfColunas(): string
    {
        $labels = [];

        foreach ($this->campos as $campo) {
            if ($campo->ehColunaVisivel()) {
                $labels[] = "'{$campo->label()}'";
            }
        }
        $labels[] = "'Status'";

        return implode(', ', $labels);
    }

    public function pdfMapRow(int $espacos = 16): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            if (! $campo->ehColunaVisivel()) {
                continue;
            }
            $linhas[] = "(string) \$registro->{$campo->nome},";
        }
        $linhas[] = '$registro->status->label(),';

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Blade form ----------------------------------------------

    public function formViewFields(int $espacos = 12): string
    {
        $linhas = array_map(
            fn (CampoModulo $campo): string => $this->campoBladeLinha($campo),
            [...$this->campos, $this->status],
        );

        return $this->bloco($linhas, $espacos);
    }

    /** Algum campo (incl. status) declara uma aba(...)? Decide card único × abas. */
    public function temAbas(): bool
    {
        if ($this->status->aba !== null) {
            return true;
        }

        foreach ($this->campos as $campo) {
            if ($campo->aba !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Campos (incl. status) agrupados por aba, na ordem de primeira aparição.
     * Campos regulares sem aba caem na primeira aba; o status sem aba, na última.
     *
     * @return array<string, list<CampoModulo>>
     */
    public function abas(): array
    {
        $ordem = [];
        foreach ([...$this->campos, $this->status] as $campo) {
            if ($campo->aba !== null && ! in_array($campo->aba, $ordem, true)) {
                $ordem[] = $campo->aba;
            }
        }

        if ($ordem === []) {
            return [];
        }

        $primeira = $ordem[0];
        $ultima = $ordem[count($ordem) - 1];

        /** @var array<string, list<CampoModulo>> $grupos */
        $grupos = array_fill_keys($ordem, []);

        foreach ($this->campos as $campo) {
            $grupos[$campo->aba ?? $primeira][] = $campo;
        }
        $grupos[$this->status->aba ?? $ultima][] = $this->status;

        return $grupos;
    }

    /**
     * Corpo do formulário (token __FORM_BODY__): card único quando não há abas,
     * ou abas conectadas (x-shared.tab-nav + tab-body) quando algum campo tem aba(...).
     */
    public function formBody(): string
    {
        return $this->temAbas() ? $this->formBodyAbas() : $this->formBodyCardUnico();
    }

    // ---- Blocos: Enum de status ------------------------------------------

    /**
     * @return list<array{case: string, value: string}>
     */
    public function statusCasos(): array
    {
        return array_map(
            static fn (string $v): array => ['case' => Str::studly($v), 'value' => $v],
            $this->status->enumValores,
        );
    }

    public function enumCases(int $espacos = 4): string
    {
        $linhas = array_map(
            static fn (array $c): string => "case {$c['case']} = '{$c['value']}';",
            $this->statusCasos(),
        );

        return $this->bloco($linhas, $espacos);
    }

    public function enumLabelArms(int $espacos = 12): string
    {
        $linhas = array_map(
            static fn (array $c): string => "self::{$c['case']} => '" . Str::ucfirst($c['value']) . "',",
            $this->statusCasos(),
        );

        return $this->bloco($linhas, $espacos);
    }

    public function enumVariantArms(int $espacos = 12): string
    {
        $linhas = [];
        foreach ($this->statusCasos() as $i => $c) {
            $variant = match (strtolower($c['value'])) {
                'ativo', 'publicado', 'aprovado', 'concluido' => 'success',
                'inativo', 'arquivado', 'cancelado', 'rejeitado' => 'default',
                'rascunho', 'pendente' => 'warning',
                default => $i === 0 ? 'success' : 'default',
            };
            $linhas[] = "self::{$c['case']} => '{$variant}',";
        }

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Teste ----------------------------------------------------

    public function testTenantSetup(int $espacos = 4): string
    {
        if (! $this->tenant) {
            return '';
        }

        $linhas = [
            '$empresa = \App\Models\Empresa::factory()->create();',
            'app(\App\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);',
        ];

        return "\n" . $this->bloco($linhas, $espacos) . "\n";
    }

    public function testSets(int $espacos = 8): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = "->set('{$campo->nome}', {$campo->valorTeste()})";
        }
        $linhas[] = "->set('status', '{$this->statusValueDefault()}')";

        return $this->bloco($linhas, $espacos);
    }

    // ---- Blocos: Lixeira (soft-delete) ------------------------------------
    // Vazios quando !softDelete. A indentação é normalizada pelo Pint/Prettier
    // após a geração (o gerador não formata a saída).

    /** Método modelClassLixeira() exigido pelo trait ComLixeira (Table). */
    public function metodoModelClassLixeira(): string
    {
        if (! $this->softDelete) {
            return '';
        }

        return <<<PHP
    /**
         * @return class-string<{$this->studly}>
         */
        protected function modelClassLixeira(): string
        {
            return {$this->studly}::class;
        }
    PHP;
    }

    /** Métodos restore()/forceDelete() da Policy. */
    public function metodosPolicyLixeira(): string
    {
        if (! $this->softDelete) {
            return '';
        }

        $base = $this->permissaoBase();

        return <<<PHP


        public function restore(AdminUser \$auth, {$this->studly} \$registro): bool
        {
            return \$auth->can('{$base}.restaurar');
        }

        public function forceDelete(AdminUser \$auth, {$this->studly} \$registro): bool
        {
            return \$auth->can('{$base}.excluir_permanente');
        }
    PHP;
    }

    /** State trashed() da factory para exercitar a restauração. */
    public function factoryTrashed(): string
    {
        if (! $this->softDelete) {
            return '';
        }

        return <<<'PHP'


    /**
     * Estado "na lixeira" (soft-deleted) para exercitar o fluxo de restauração.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now()->subDay(),
        ]);
    }
    PHP;
    }

    /** Teste de soft-delete (excluir → lixeira → restaurar) no módulo gerado. */
    public function testeSoftDelete(): string
    {
        if (! $this->softDelete) {
            return '';
        }

        $studly = $this->studly;
        $label = mb_strtolower($this->studly);
        $tableFqn = '\\' . $this->nsLivewire() . '\\' . $studly . 'Table';

        return <<<PHP


    it('move um registro de {$label} para a lixeira e o restaura', function () {
        \$registro = {$studly}::factory()->create();

        Livewire::actingAs(\$this->admin, 'admin')
            ->test({$tableFqn}::class)
            ->call('excluir', \$registro->id)
            ->assertHasNoErrors();

        expect({$studly}::query()->whereKey(\$registro->id)->exists())->toBeFalse();

        Livewire::actingAs(\$this->admin, 'admin')
            ->test({$tableFqn}::class)
            ->call('restaurar', \$registro->id)
            ->assertHasNoErrors();

        expect({$studly}::query()->whereKey(\$registro->id)->exists())->toBeTrue();
    });
    PHP;
    }

    // ---- Destino: namespaces, views e rotas (app vs pacote) ---------------
    // Quando $pacote é null o módulo nasce em app/ (App\...); quando é um
    // Extensao, nasce no pacote (HT2ML\{Modulo}\...). Ver ADR-0015.

    private function nsBase(): string
    {
        return $this->pacote !== null ? $this->pacote->namespaceBase : 'App';
    }

    /**
     * Método permissaoListagem() exigido pelo trait FiltraPorMultiEmpresa. A
     * indentação é normalizada pelo Pint após a geração.
     */
    private function metodoPermissaoListagem(): string
    {
        $permissao = $this->snakePlural() . '.listar';

        return <<<PHP
    protected function permissaoListagem(): string
        {
            return '{$permissao}';
        }
    PHP;
    }

    private function formBodyCardUnico(): string
    {
        return implode("\n", [
            '    <x-shared.card title="Dados">',
            '        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">',
            $this->formViewFields(12),
            '        </div>',
            '    </x-shared.card>',
        ]);
    }

    private function formBodyAbas(): string
    {
        $triggers = [];
        $panels = [];
        $primeira = true;

        foreach ($this->abas() as $rotulo => $campos) {
            $id = 'aba-' . Str::slug($rotulo);
            $active = $primeira ? ' active' : '';

            $nomes = implode(', ', array_map(
                static fn (CampoModulo $campo): string => "'{$campo->nome}'",
                $campos,
            ));

            $triggers[] = "        <x-shared.tab-trigger id=\"{$id}\"{$active} :has-error=\"\$errors->hasAny([{$nomes}])\">{$rotulo}</x-shared.tab-trigger>";

            $campoLinhas = implode("\n", array_map(
                fn (CampoModulo $campo): string => '                ' . $this->campoBladeLinha($campo),
                $campos,
            ));

            $panels[] = implode("\n", [
                "        <x-shared.tab-panel id=\"{$id}\"{$active}>",
                '            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">',
                $campoLinhas,
                '            </div>',
                '        </x-shared.tab-panel>',
            ]);

            $primeira = false;
        }

        // A conexão sem gap é garantida pelo próprio x-shared.tab-nav (mb-0!),
        // que anula o space-y-6 da view-pai e cola a nav no tab-body.
        return implode("\n", [
            '    <x-shared.tab-nav>',
            implode("\n", $triggers),
            '    </x-shared.tab-nav>',
            '',
            '    <x-shared.tab-body>',
            implode("\n\n", $panels),
            '    </x-shared.tab-body>',
        ]);
    }

    /** Linha Blade de um campo no formulário (status é select com options do Enum). */
    private function campoBladeLinha(CampoModulo $campo): string
    {
        if ($campo->ehStatus()) {
            return '<x-shared.select-search name="status" label="Status" wire:model="status" :options="\App\Enums\\' . $this->statusEnumShort() . '::options()" required />';
        }

        return $campo->componenteBlade();
    }

    // ---- Helpers de bloco -------------------------------------------------

    /**
     * @param  list<string>  $linhas
     */
    private function bloco(array $linhas, int $espacos): string
    {
        if ($linhas === []) {
            return '';
        }

        $ident = str_repeat(' ', $espacos);

        return implode("\n", array_map(static fn (string $l): string => $ident . $l, $linhas));
    }

    private function dtoFromArrayLinha(CampoModulo $campo): string
    {
        $chave = $campo->nome;
        $prop = $campo->camel();

        return match ($campo->tipo) {
            'integer', 'money' => "{$prop}: (int) (\$data['{$chave}'] ?? 0),",
            'boolean' => "{$prop}: (bool) (\$data['{$chave}'] ?? false),",
            'multiselect' => "{$prop}: (array) (\$data['{$chave}'] ?? []),",
            'richtext' => $campo->nullable
                ? "{$prop}: \$html('{$chave}'),"
                : "{$prop}: \HT2ML\Core\Support\Html\HtmlSanitizer::clean((string) (\$data['{$chave}'] ?? '')),",
            default => $campo->nullable
                ? "{$prop}: \$texto('{$chave}'),"
                : "{$prop}: (string) (\$data['{$chave}'] ?? ''),",
        };
    }

    private function regraUnique(CampoModulo $campo): string
    {
        return "Rule::unique('{$this->tabela()}', '{$campo->nome}')->ignore(\$ignorarId)";
    }
}
