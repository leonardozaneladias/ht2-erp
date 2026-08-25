<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Generator;

use Illuminate\Support\Str;

/**
 * Um campo declarado na spec do gerador (`--fields="nome:tipo:modificador"`).
 *
 * Concentra TODO o mapeamento "um tipo de campo -> artefato": coluna de
 * migration, cast do model, regra de validacao, tipo PHP do DTO, componente
 * Blade do formulario, coluna/filtro do PowerGrid e fragmento de factory.
 * Assim o gerador (GeradorModulo) so orquestra; a inteligencia por campo
 * mora aqui e fica testavel isoladamente.
 *
 * Tipos suportados: string, text, richtext, integer, money, decimal, boolean,
 * date, datetime, email, url, cnpj, cpf, cep, phone, color,
 * enum(a|b|c), multiselect(a|b|c).
 *
 * Upload de arquivo (image/file) NÃO é scaffoldado: exige WithFileUploads +
 * disco + prop não-tipada. Adicione manualmente seguindo AbaLogin/PerfilConta.
 */
final class CampoModulo
{
    /**
     * @param  list<string>  $enumValores  valores quando $tipo === 'enum'|'multiselect'
     * @param  string|null  $aba  rótulo da aba do formulário (modificador aba(...))
     */
    public function __construct(
        public readonly string $nome,
        public readonly string $tipo,
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
        public readonly array $enumValores = [],
        public readonly ?string $aba = null,
    ) {}

    /**
     * Faz o parse de um token "nome:tipo:mod1:mod2" (ex.: "preco:money",
     * "sku:string:unique", "descricao:text:nullable", "status:enum(a|b|c)",
     * "email:email:aba(Contato)"). Tipos parametrizados — enum(a|b|c) e
     * multiselect(a|b|c) — usam "|" entre valores (vírgula separa campos).
     */
    public static function deToken(string $token): self
    {
        $partes = array_values(array_filter(array_map('trim', explode(':', trim($token))), static fn (string $valor): bool => $valor !== ''));

        $nome = Str::snake((string) ($partes[0] ?? ''));
        $tipoBruto = strtolower($partes[1] ?? 'string');

        // Modificadores em caixa original (para preservar o rótulo da aba) e a
        // versão lower (para checar flags como nullable/unique).
        $modificadoresOriginais = array_slice($partes, 2);
        $modificadores = array_map('strtolower', $modificadoresOriginais);

        $enumValores = [];
        $tipo = $tipoBruto;

        // enum(a|b|c) e multiselect(a|b|c): extrai os valores entre parênteses.
        if (preg_match('/^(enum|multiselect)\((.+)\)$/', $tipoBruto, $m) === 1) {
            $tipo = $m[1];
            $enumValores = array_values(array_filter(
                array_map('trim', preg_split('/[|,]/', $m[2]) ?: []),
                static fn (string $valor): bool => $valor !== '',
            ));
        }

        // aba(Rótulo): agrupa o campo numa aba do formulário (case preservado).
        $aba = null;
        foreach ($modificadoresOriginais as $mod) {
            if (preg_match('/^aba\((.+)\)$/i', trim($mod), $ma) === 1) {
                $aba = trim($ma[1]);
            }
        }

        return new self(
            nome: $nome,
            tipo: $tipo,
            nullable: in_array('nullable', $modificadores, true),
            unique: in_array('unique', $modificadores, true),
            enumValores: $enumValores,
            aba: $aba,
        );
    }

    public function ehStatus(): bool
    {
        return $this->tipo === 'enum' && $this->nome === 'status';
    }

    public function ehEnum(): bool
    {
        return $this->tipo === 'enum';
    }

    public function label(): string
    {
        return Str::ucfirst(str_replace('_', ' ', $this->nome));
    }

    public function camel(): string
    {
        return Str::camel($this->nome);
    }

    /** Slug do rótulo da aba (id do tab-panel); vazio quando o campo não tem aba. */
    public function abaSlug(): string
    {
        return $this->aba !== null ? Str::slug($this->aba) : '';
    }

    /** Valores de enum/multiselect como lista PHP inline: 'a', 'b', 'c'. */
    public function enumValoresPhp(): string
    {
        return implode(', ', array_map(static fn (string $v): string => "'{$v}'", $this->enumValores));
    }

    /** Opções value=>label inline para selects de enum/multiselect não-status. */
    public function enumOptionsPhp(): string
    {
        return implode(', ', array_map(
            static fn (string $v): string => "'{$v}' => '" . Str::ucfirst(str_replace('_', ' ', $v)) . "'",
            $this->enumValores,
        ));
    }

    // ---- Migration --------------------------------------------------------

    public function colunaMigration(): string
    {
        $coluna = match ($this->tipo) {
            'text', 'richtext' => "\$table->text('{$this->nome}')",
            'integer', 'money' => "\$table->integer('{$this->nome}')",
            'decimal' => "\$table->decimal('{$this->nome}', 10, 2)",
            'boolean' => "\$table->boolean('{$this->nome}')->default(false)",
            'date' => "\$table->date('{$this->nome}')",
            'datetime' => "\$table->timestamp('{$this->nome}')",
            'cnpj' => "\$table->string('{$this->nome}', 18)",
            'cpf' => "\$table->string('{$this->nome}', 14)",
            'pis' => "\$table->string('{$this->nome}', 11)",
            'cep' => "\$table->string('{$this->nome}', 9)",
            'phone' => "\$table->string('{$this->nome}', 20)",
            'color' => "\$table->string('{$this->nome}', 9)",
            'multiselect' => "\$table->json('{$this->nome}')",
            default => "\$table->string('{$this->nome}')",
        };

        if ($this->nullable) {
            $coluna .= '->nullable()';
        }

        if ($this->unique) {
            $coluna .= '->unique()';
        }

        return $coluna . ';';
    }

    public function precisaIndice(): bool
    {
        return $this->ehStatus() || in_array($this->tipo, ['date', 'datetime'], true);
    }

    // ---- Model ------------------------------------------------------------

    public function castModel(?string $enumFqcn = null): ?string
    {
        return match (true) {
            $this->ehStatus() && $enumFqcn !== null => "'{$this->nome}' => {$enumFqcn}::class",
            $this->tipo === 'boolean' => "'{$this->nome}' => 'boolean'",
            $this->tipo === 'integer' => "'{$this->nome}' => 'integer'",
            $this->tipo === 'money' => "'{$this->nome}' => \\HT2ML\\Core\\Casts\\MoneyCast::class",
            $this->tipo === 'decimal' => "'{$this->nome}' => 'decimal:2'",
            $this->tipo === 'date' => "'{$this->nome}' => 'date'",
            $this->tipo === 'datetime' => "'{$this->nome}' => 'datetime'",
            $this->tipo === 'multiselect' => "'{$this->nome}' => 'array'",
            default => null,
        };
    }

    // ---- DTO --------------------------------------------------------------

    public function tipoPhp(?string $enumShort = null): string
    {
        $base = match ($this->tipo) {
            'integer', 'money' => 'int',
            'boolean' => 'bool',
            'multiselect' => 'array',
            'enum' => $enumShort ?? 'string',
            default => 'string',
        };

        // Enum de status nunca e nullable; multiselect e sempre array (default []);
        // os demais respeitam o modificador.
        $prefixoNull = ($this->nullable && ! $this->ehStatus() && $this->tipo !== 'multiselect') ? '?' : '';

        return $prefixoNull . $base;
    }

    public function defaultPhp(): string
    {
        return match (true) {
            $this->ehStatus() => '', // tratado a parte (enum default)
            $this->tipo === 'boolean' => ' = false',
            $this->tipo === 'multiselect' => ' = []',
            $this->nullable => ' = null',
            $this->tipo === 'integer' || $this->tipo === 'money' => ' = 0',
            default => " = ''",
        };
    }

    // ---- Livewire form prop ----------------------------------------------

    public function defaultLivewire(): string
    {
        return match (true) {
            $this->tipo === 'boolean' => 'false',
            $this->tipo === 'integer' || $this->tipo === 'money' => '0',
            $this->tipo === 'multiselect' => '[]',
            $this->nullable => 'null',
            default => "''",
        };
    }

    public function tipoLivewire(): string
    {
        $base = match ($this->tipo) {
            'integer', 'money' => 'int',
            'boolean' => 'bool',
            'multiselect' => 'array',
            default => 'string',
        };

        return ($this->nullable && ! in_array($this->tipo, ['boolean', 'multiselect'], true)) ? '?' . $base : $base;
    }

    // ---- Validacao --------------------------------------------------------

    /**
     * @param  string|null  $enumShort  nome curto do Enum (para Rule::enum)
     * @return list<string> itens da regra (renderizados como array PHP)
     */
    public function regras(?string $enumShort = null): array
    {
        $obrig = $this->nullable ? "'nullable'" : "'required'";

        return match ($this->tipo) {
            'text', 'richtext' => [$this->nullable ? "'nullable'" : "'required'", "'string'"],
            'integer' => [$obrig, "'integer'"],
            'money' => [$obrig, "'integer'", "'min:0'"],
            'decimal' => [$obrig, "'numeric'", "'min:0'"],
            'boolean' => ["'boolean'"],
            'date', 'datetime' => [$this->nullable ? "'nullable'" : "'required'", "'date'"],
            'email' => [$this->nullable ? "'nullable'" : "'required'", "'email:rfc'", "'max:191'"],
            'url' => [$this->nullable ? "'nullable'" : "'required'", "'url'", "'max:255'"],
            'cnpj' => [$obrig, 'new \\HT2ML\\Core\\Rules\\Cnpj()'],
            'cpf' => [$obrig, 'new \\HT2ML\\Core\\Rules\\Cpf()'],
            'pis' => [$obrig, 'new \\HT2ML\\Core\\Rules\\Pis()'],
            'cep' => [$obrig, "'string'", "'max:9'"],
            'phone' => [$obrig, "'string'", "'max:20'"],
            'color' => [$obrig, "'string'", "'max:9'"],
            'enum' => [$obrig, $enumShort !== null ? "Rule::enum({$enumShort}::class)" : "Rule::in([{$this->enumValoresPhp()}])"],
            'multiselect' => [$this->nullable ? "'nullable'" : "'required'", "'array'"],
            default => [$obrig, "'string'", "'max:255'"],
        };
    }

    public function usaRuleNaValidacao(): bool
    {
        // Rule::unique / Rule::enum / Rule::in (enum não-status também usa Rule::in).
        // HT2ML\Core\Rules\Cpf / Cnpj / Pis também são objetos, não strings.
        return $this->unique || $this->ehEnum() || in_array($this->tipo, ['cpf', 'cnpj', 'pis'], true);
    }

    // ---- Factory ----------------------------------------------------------

    public function fragmentoFactory(?string $enumShort = null): string
    {
        $valor = match ($this->tipo) {
            'text', 'richtext' => 'fake()->sentence()',
            'integer' => 'fake()->numberBetween(1, 1000)',
            'money' => 'fake()->numberBetween(1000, 500000)',
            'decimal' => 'fake()->randomFloat(2, 1, 1000)',
            'boolean' => 'fake()->boolean()',
            'date', 'datetime' => 'fake()->dateTimeBetween(\'-1 year\')',
            'email' => 'fake()->unique()->safeEmail()',
            'url' => 'fake()->url()',
            'cnpj' => 'fake()->numerify(\'##.###.###/####-##\')',
            'cpf' => 'fake()->numerify(\'###.###.###-##\')',
            'pis' => 'fake()->numerify(\'###.#####.##-#\')',
            'cep' => 'fake()->numerify(\'#####-###\')',
            'phone' => 'fake()->numerify(\'(##) #####-####\')',
            'color' => 'fake()->hexColor()',
            'enum' => $enumShort !== null ? "fake()->randomElement({$enumShort}::cases())" : "fake()->randomElement([{$this->enumValoresPhp()}])",
            'multiselect' => "fake()->randomElements([{$this->enumValoresPhp()}], 2)",
            default => 'fake()->words(2, true)',
        };

        return "'{$this->nome}' => {$valor},";
    }

    /**
     * Valor literal PHP válido para preencher este campo num teste Livewire
     * (`->set('campo', <valor>)`), respeitando as regras geradas.
     */
    public function valorTeste(): string
    {
        return match ($this->tipo) {
            'integer' => '1',
            'money' => '1990',
            'decimal' => "'10.50'",
            'boolean' => 'true',
            'date', 'datetime' => "'2026-01-15'",
            'email' => "'contato@exemplo.com'",
            'url' => "'https://exemplo.com'",
            'cnpj' => "'11.222.333/0001-81'",
            'cpf' => "'111.444.777-35'",
            'pis' => "'12345678919'",
            'cep' => "'01001-000'",
            'phone' => "'(11) 99999-9999'",
            'color' => "'#3b82f6'",
            'text', 'richtext' => "'Descrição de exemplo gerada pelo teste.'",
            'enum' => "'" . ($this->enumValores[0] ?? 'opcao') . "'",
            'multiselect' => "['" . ($this->enumValores[0] ?? 'opcao') . "']",
            default => "'Exemplo de {$this->label()}'",
        };
    }

    // ---- PowerGrid --------------------------------------------------------

    public function ehColunaVisivel(): bool
    {
        // Campos longos/binarios/compostos nao viram coluna por padrao.
        return ! in_array($this->tipo, ['text', 'richtext', 'multiselect'], true);
    }

    public function filtroPowerGrid(): ?string
    {
        return match ($this->tipo) {
            'boolean' => "Filter::boolean('{$this->nome}')",
            'enum' => null, // tratado a parte (multiSelect com cases do Enum)
            'string', 'email', 'url', 'cnpj', 'cpf', 'pis', 'cep', 'phone' => "Filter::inputText('{$this->nome}')->placeholder('Filtrar por {$this->label()}')",
            default => null,
        };
    }

    // ---- Blade form -------------------------------------------------------

    /**
     * Componente Blade do formulario para este campo (sem o status, que e
     * tratado a parte com combobox + options do Enum).
     */
    public function componenteBlade(): string
    {
        $nome = $this->nome;
        $label = $this->label();
        $req = $this->nullable ? '' : ' required';

        return match ($this->tipo) {
            'text' => "<x-shared.textarea name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'richtext' => "<x-shared.rich-editor name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'money' => "<x-shared.money-input name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'decimal' => "<x-shared.input type=\"number\" step=\"0.01\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'boolean' => "<x-shared.toggle name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\" stacked />",
            'date', 'datetime' => "<x-shared.date-picker name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'cnpj' => "<x-shared.cnpj-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'cpf' => "<x-shared.cpf-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'pis' => "<x-shared.input name=\"{$nome}\" label=\"PIS/PASEP\" wire:model=\"{$nome}\" placeholder=\"000.00000.00-0\"{$req} />",
            'cep' => "<x-shared.cep-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'phone' => "<x-shared.phone-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'email' => "<x-shared.input type=\"email\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'url' => "<x-shared.input type=\"url\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\" placeholder=\"https://\"{$req} />",
            'integer' => "<x-shared.input type=\"number\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'color' => "<x-shared.color-picker name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"" . ($this->nullable ? ' clearable' : ' required') . ' />',
            'enum' => "<x-shared.select-search name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\" :options=\"[{$this->enumOptionsPhp()}]\"{$req} />",
            'multiselect' => "<x-shared.select-search name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\" :options=\"[{$this->enumOptionsPhp()}]\" multiple{$req} />",
            default => "<x-shared.input name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
        };
    }

    /**
     * Linhas da ficha de visualização ("Ver") deste campo — um
     * x-shared.field-display com o valor formatado por tipo
     * (ver docs/visualizacao.md).
     *
     * @return list<string>
     */
    public function componenteFicha(): array
    {
        $nome = $this->nome;
        $label = $this->label();

        if ($this->ehStatus()) {
            return [
                '<x-shared.field-display label="Status">',
                '    <x-shared.badge :variant="$registro->status->variant()" pill size="sm">{{ $registro->status->label() }}</x-shared.badge>',
                '</x-shared.field-display>',
            ];
        }

        if ($this->tipo === 'color') {
            return [
                "<x-shared.field-display label=\"{$label}\">",
                '    <span class="inline-flex items-center gap-2">',
                "        <span class=\"border-default-300 size-4 rounded border\" style=\"background: {{ \$registro->{$nome} }}\"></span>",
                "        {{ \$registro->{$nome} ?: '—' }}",
                '    </span>',
                '</x-shared.field-display>',
            ];
        }

        $valor = match ($this->tipo) {
            'money' => "{{ \$registro->{$nome}?->formatado() ?? '—' }}",
            'decimal' => "{{ \$registro->{$nome} !== null ? number_format((float) \$registro->{$nome}, 2, ',', '.') : '—' }}",
            'boolean' => "{{ \$registro->{$nome} ? 'Sim' : 'Não' }}",
            'date' => "{{ \$registro->{$nome}?->format('d/m/Y') ?? '—' }}",
            'datetime' => "{{ \$registro->{$nome}?->format('d/m/Y H:i') ?? '—' }}",
            'multiselect' => "{{ filled(\$registro->{$nome}) ? implode(', ', (array) \$registro->{$nome}) : '—' }}",
            // richtext é sanitizado na gravação (HtmlSanitizer no DTO).
            'richtext' => "{!! \$registro->{$nome} ?: '—' !!}",
            'enum' => "{{ \$registro->{$nome} ? ucfirst(str_replace('_', ' ', (string) \$registro->{$nome})) : '—' }}",
            default => "{{ \$registro->{$nome} ?: '—' }}",
        };

        return ["<x-shared.field-display label=\"{$label}\">{$valor}</x-shared.field-display>"];
    }
}
