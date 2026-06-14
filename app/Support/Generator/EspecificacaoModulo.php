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
        $base = $this->snakePlural();
        $singular = mb_strtolower($this->studly);
        $plural = mb_strtolower($this->studlyPlural);

        return [
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
                'descricao' => "Remover {$plural}.",
            ],
        ];
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
            '__MODULO_ROTA_NOME__' => $this->snakePlural(),
            '__MODULO_PARAM__' => $this->snake(),
            '__STATUS_ENUM__' => $this->statusEnumShort(),
            '__STATUS_CASE_DEFAULT__' => $this->statusCaseDefault(),
            '__STATUS_VALUE_DEFAULT__' => $this->statusValueDefault(),
            '__MODEL_USE_TENANT__' => $this->tenant ? 'use App\Models\Concerns\BelongsToEmpresa;' : '',
            '__MODEL_TRAIT_TENANT__' => $this->tenant ? 'use BelongsToEmpresa;' : '',
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
            if ($campo->nullable && ! in_array($campo->tipo, ['integer', 'money', 'boolean'], true)) {
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
            $linhas[] = "public {$campo->tipoLivewire()} \${$campo->nome} = {$campo->defaultLivewire()};";
        }
        $linhas[] = "public string \$status = '{$this->statusValueDefault()}';";

        return $this->bloco($linhas, $espacos);
    }

    public function formMountLoad(int $espacos = 8): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $cast = match ($campo->tipo) {
                'integer', 'money' => '(int) ',
                'boolean' => '(bool) ',
                default => $campo->nullable ? '' : '(string) ',
            };
            $linhas[] = "\$this->{$campo->nome} = {$cast}\$registro->{$campo->nome};";
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

    // ---- Blocos: Blade form ----------------------------------------------

    public function formViewFields(int $espacos = 12): string
    {
        $linhas = [];

        foreach ($this->campos as $campo) {
            $linhas[] = $campo->componenteBlade();
        }

        // status: select pesquisável com options do Enum (FQCN no Blade)
        $linhas[] = '<x-shared.select name="status" label="Status" wire:model="status" :options="\App\Enums\\' . $this->statusEnumShort() . '::options()" required />';

        return $this->bloco($linhas, $espacos);
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
