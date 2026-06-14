<?php

declare(strict_types=1);

namespace App\Support\Generator;

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
 * Tipos suportados: string, text, integer, money, boolean, date, datetime,
 * email, cnpj, cpf, cep, phone, enum(a|b|c).
 */
final class CampoModulo
{
    /**
     * @param  list<string>  $enumValores  valores quando $tipo === 'enum'
     */
    public function __construct(
        public readonly string $nome,
        public readonly string $tipo,
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
        public readonly array $enumValores = [],
    ) {}

    /**
     * Faz o parse de um token "nome:tipo:mod1:mod2" (ex.: "preco:money",
     * "sku:string:unique", "descricao:text:nullable", "status:enum(a|b|c)").
     */
    public static function deToken(string $token): self
    {
        $partes = array_values(array_filter(array_map('trim', explode(':', trim($token))), static fn (string $valor): bool => $valor !== ''));

        $nome = Str::snake((string) ($partes[0] ?? ''));
        $tipoBruto = strtolower($partes[1] ?? 'string');
        $modificadores = array_map('strtolower', array_slice($partes, 2));

        $enumValores = [];
        $tipo = $tipoBruto;

        if (str_starts_with($tipoBruto, 'enum(') && str_ends_with($tipoBruto, ')')) {
            $tipo = 'enum';
            $conteudo = substr($tipoBruto, 5, -1);
            $enumValores = array_values(array_filter(
                array_map('trim', preg_split('/[|,]/', $conteudo) ?: []),
                static fn (string $valor): bool => $valor !== '',
            ));
        }

        return new self(
            nome: $nome,
            tipo: $tipo,
            nullable: in_array('nullable', $modificadores, true),
            unique: in_array('unique', $modificadores, true),
            enumValores: $enumValores,
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

    // ---- Migration --------------------------------------------------------

    public function colunaMigration(): string
    {
        $coluna = match ($this->tipo) {
            'text' => "\$table->text('{$this->nome}')",
            'integer', 'money' => "\$table->integer('{$this->nome}')",
            'boolean' => "\$table->boolean('{$this->nome}')->default(false)",
            'date' => "\$table->date('{$this->nome}')",
            'datetime' => "\$table->timestamp('{$this->nome}')",
            'cnpj' => "\$table->string('{$this->nome}', 18)",
            'cpf' => "\$table->string('{$this->nome}', 14)",
            'cep' => "\$table->string('{$this->nome}', 9)",
            'phone' => "\$table->string('{$this->nome}', 20)",
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
            $this->tipo === 'integer' || $this->tipo === 'money' => "'{$this->nome}' => 'integer'",
            $this->tipo === 'date' => "'{$this->nome}' => 'date'",
            $this->tipo === 'datetime' => "'{$this->nome}' => 'datetime'",
            default => null,
        };
    }

    // ---- DTO --------------------------------------------------------------

    public function tipoPhp(?string $enumShort = null): string
    {
        $base = match ($this->tipo) {
            'integer', 'money' => 'int',
            'boolean' => 'bool',
            'enum' => $enumShort ?? 'string',
            default => 'string',
        };

        // Enum de status nunca e nullable; os demais respeitam o modificador.
        $prefixoNull = ($this->nullable && ! $this->ehStatus()) ? '?' : '';

        return $prefixoNull . $base;
    }

    public function defaultPhp(): string
    {
        return match (true) {
            $this->ehStatus() => '', // tratado a parte (enum default)
            $this->tipo === 'boolean' => ' = false',
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
            $this->nullable => 'null',
            default => "''",
        };
    }

    public function tipoLivewire(): string
    {
        $base = match ($this->tipo) {
            'integer', 'money' => 'int',
            'boolean' => 'bool',
            default => 'string',
        };

        return ($this->nullable && $this->tipo !== 'boolean') ? '?' . $base : $base;
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
            'text' => [$this->nullable ? "'nullable'" : "'required'", "'string'"],
            'integer' => [$obrig, "'integer'"],
            'money' => [$obrig, "'integer'", "'min:0'"],
            'boolean' => ["'boolean'"],
            'date', 'datetime' => [$this->nullable ? "'nullable'" : "'required'", "'date'"],
            'email' => [$this->nullable ? "'nullable'" : "'required'", "'email:rfc'", "'max:191'"],
            'cnpj' => [$obrig, "'string'", "'max:18'"],
            'cpf' => [$obrig, "'string'", "'max:14'"],
            'cep' => [$obrig, "'string'", "'max:9'"],
            'phone' => [$obrig, "'string'", "'max:20'"],
            'enum' => [$obrig, $enumShort !== null ? "Rule::enum({$enumShort}::class)" : "'string'"],
            default => [$obrig, "'string'", "'max:255'"],
        };
    }

    public function usaRuleNaValidacao(): bool
    {
        return $this->unique || $this->ehEnum();
    }

    // ---- Factory ----------------------------------------------------------

    public function fragmentoFactory(?string $enumShort = null): string
    {
        $valor = match ($this->tipo) {
            'text' => 'fake()->sentence()',
            'integer' => 'fake()->numberBetween(1, 1000)',
            'money' => 'fake()->numberBetween(1000, 500000)',
            'boolean' => 'fake()->boolean()',
            'date', 'datetime' => 'fake()->dateTimeBetween(\'-1 year\')',
            'email' => 'fake()->unique()->safeEmail()',
            'cnpj' => 'fake()->numerify(\'##.###.###/####-##\')',
            'cpf' => 'fake()->numerify(\'###.###.###-##\')',
            'cep' => 'fake()->numerify(\'#####-###\')',
            'phone' => 'fake()->numerify(\'(##) #####-####\')',
            'enum' => $enumShort !== null ? "fake()->randomElement({$enumShort}::cases())" : 'fake()->word()',
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
            'boolean' => 'true',
            'date', 'datetime' => "'2026-01-15'",
            'email' => "'contato@exemplo.com'",
            'cnpj' => "'11.222.333/0001-81'",
            'cpf' => "'529.982.247-25'",
            'cep' => "'01001-000'",
            'phone' => "'(11) 99999-9999'",
            'text' => "'Descrição de exemplo gerada pelo teste.'",
            default => "'Exemplo de {$this->label()}'",
        };
    }

    // ---- PowerGrid --------------------------------------------------------

    public function ehColunaVisivel(): bool
    {
        // Campos longos/binarios nao viram coluna por padrao.
        return ! in_array($this->tipo, ['text'], true);
    }

    public function filtroPowerGrid(): ?string
    {
        return match ($this->tipo) {
            'boolean' => "Filter::boolean('{$this->nome}')",
            'enum' => null, // tratado a parte (multiSelect com cases do Enum)
            'string', 'email', 'cnpj', 'cpf', 'cep', 'phone' => "Filter::inputText('{$this->nome}')->placeholder('Filtrar por {$this->label()}')",
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
            // Valor monetário em centavos (§5.3). A máscara x-shared.money-input
            // grava string formatada; até existir um caster string->centavos no
            // form, o gerador usa input numérico (centavos) para ficar correto
            // ponta a ponta. Troque por money-input quando adicionar o caster.
            'money' => "<x-shared.input type=\"number\" min=\"0\" step=\"1\" name=\"{$nome}\" label=\"{$label} (centavos)\" wire:model=\"{$nome}\"{$req} />",
            'boolean' => "<x-shared.toggle name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\" />",
            'date', 'datetime' => "<x-shared.date-picker name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'cnpj' => "<x-shared.cnpj-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'cpf' => "<x-shared.cpf-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'cep' => "<x-shared.cep-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'phone' => "<x-shared.phone-input name=\"{$nome}\" wire:model=\"{$nome}\"{$req} />",
            'email' => "<x-shared.input type=\"email\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            'integer' => "<x-shared.input type=\"number\" name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
            default => "<x-shared.input name=\"{$nome}\" label=\"{$label}\" wire:model=\"{$nome}\"{$req} />",
        };
    }
}
