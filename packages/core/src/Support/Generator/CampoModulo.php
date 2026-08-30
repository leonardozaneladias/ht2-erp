<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Generator;

use Illuminate\Database\Eloquent\Model;
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
        /** Model relacionado quando $tipo === 'relacao' (curto ou FQCN). */
        public readonly ?string $relacaoModel = null,
        /** Atributo exibido do relacionado (coluna, select, ficha). */
        public readonly string $relacaoAtributo = 'nome',
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
        // O tipo cru preserva a caixa: `relacao(Turma)` precisa do Studly, e
        // strtolower() o destruiria antes do parse.
        $tipoCru = $partes[1] ?? 'string';
        $tipoBruto = strtolower($tipoCru);

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

        // relacao(Model) ou relacao(Model:atributo). O Model pode ser curto — e
        // aí vive no mesmo namespace do recurso — ou FQCN, para apontar para
        // outro pacote (ex.: \HT2ML\Core\Models\Filial).
        $relacaoModel = null;
        $relacaoAtributo = 'nome';

        if (preg_match('/^relacao\((.+)\)$/i', trim($tipoCru), $mr) === 1) {
            $tipo = 'relacao';
            $alvo = trim($mr[1]);

            // Barra vertical separa o atributo, e não dois-pontos: o token já
            // foi quebrado por ':' lá em cima, então `relacao(Turma:descricao)`
            // chegaria aqui partido ao meio. É a mesma razão de enum(a|b|c).
            if (preg_match('/^(.+?)\|([A-Za-z_][A-Za-z0-9_]*)$/', $alvo, $ma) === 1) {
                $alvo = trim($ma[1]);
                $relacaoAtributo = $ma[2];
            }

            $relacaoModel = ltrim($alvo, '\\');
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
            relacaoModel: $relacaoModel,
            relacaoAtributo: $relacaoAtributo,
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

    public function ehRelacao(): bool
    {
        return $this->tipo === 'relacao' && $this->relacaoModel !== null;
    }

    /** Nome curto do model relacionado: 'Turma' em \Pacote\Models\Turma. */
    public function relacaoModelCurto(): string
    {
        return Str::afterLast((string) $this->relacaoModel, '\\');
    }

    /** O método da relação: `turma_id` -> `turma`. */
    public function relacaoMetodo(): string
    {
        return Str::camel(Str::beforeLast($this->nome, '_id') ?: $this->nome);
    }

    /**
     * A tabela apontada pela FK.
     *
     * O palpite é o pluralizador do Laravel, que é INGLÊS: 'Filial' vira
     * 'filials' e a FK aponta para uma tabela que não existe — medido ao gerar
     * a primeira relação. Quando o model já existe, quem responde é ele
     * (getTable()), que conhece o próprio `$table`; o palpite só vale para um
     * model que ainda vai ser gerado, e aí o gerador nomeia a tabela com a
     * mesma regra.
     */
    public function relacaoTabela(?string $fqcn = null): string
    {
        if ($fqcn !== null && class_exists($fqcn) && is_subclass_of($fqcn, Model::class)) {
            /** @var Model $instancia */
            $instancia = new $fqcn;

            return $instancia->getTable();
        }

        return Str::snake(Str::pluralStudly($this->relacaoModelCurto()));
    }

    /**
     * FQCN do relacionado. Nome curto resolve no namespace de models do próprio
     * recurso; com barra, o autor já disse onde ele mora.
     */
    public function relacaoFqcn(string $nsModelsDoRecurso): string
    {
        $model = (string) $this->relacaoModel;

        return str_contains($model, '\\') ? $model : $nsModelsDoRecurso . '\\' . $model;
    }

    public function label(): string
    {
        // Numa relação o rótulo é o do RELACIONADO, não o da coluna: quem lê a
        // tela vê "Turma", não "Turma id".
        $base = $this->ehRelacao() ? Str::beforeLast($this->nome, '_id') : $this->nome;

        return Str::ucfirst(str_replace('_', ' ', $base ?: $this->nome));
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

    /**
     * @param  string|null  $tabelaFqcn  FQCN do relacionado, para perguntar a tabela a ele
     */
    public function colunaMigration(?string $tabelaFqcn = null): string
    {
        if ($this->ehRelacao()) {
            // foreignId + constrained: a integridade fica no banco, não no
            // combinado. nullOnDelete quando o campo aceita nulo, cascade não —
            // apagar uma turma não pode apagar os alunos dela em silêncio.
            $fk = "\$table->foreignId('{$this->nome}')";
            $fk .= $this->nullable ? '->nullable()' : '';
            $fk .= "->constrained('{$this->relacaoTabela($tabelaFqcn)}')";
            $fk .= $this->nullable ? '->nullOnDelete()' : '->restrictOnDelete()';

            return $fk . ';';
        }

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
            'relacao', 'integer', 'money' => 'int',
            'boolean' => 'bool',
            'multiselect' => 'array',
            'enum' => $enumShort ?? 'string',
            default => 'string',
        };

        // FK é sempre anulável: a propriedade nasce nula ("nada selecionado")
        // mesmo quando o campo é obrigatório, e o `required` cobra a escolha.
        // Um `int` sem `?` estouraria TypeError ao abrir o formulário de criar.
        if ($this->ehRelacao()) {
            return '?' . $base;
        }

        // Enum de status nunca e nullable; multiselect e sempre array (default []);
        // os demais respeitam o modificador.
        $prefixoNull = ($this->nullable && ! $this->ehStatus() && $this->tipo !== 'multiselect') ? '?' : '';

        return $prefixoNull . $base;
    }

    public function defaultPhp(): string
    {
        return match (true) {
            $this->ehStatus() => '', // tratado a parte (enum default)
            $this->ehRelacao() => ' = null',
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
            // FK não tem valor neutro: 0 não existe na tabela e '' não é int.
            // Nasce nula — "nada selecionado" — e o `required` cobra a escolha.
            $this->ehRelacao() => 'null',
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
            'relacao', 'integer', 'money' => 'int',
            'boolean' => 'bool',
            'multiselect' => 'array',
            default => 'string',
        };

        // Sempre anulável numa FK: a propriedade nasce nula (nada selecionado),
        // então o tipo precisa aceitar isso mesmo quando o campo é obrigatório.
        if ($this->ehRelacao()) {
            return '?' . $base;
        }

        return ($this->nullable && ! in_array($this->tipo, ['boolean', 'multiselect'], true)) ? '?' . $base : $base;
    }

    // ---- Validacao --------------------------------------------------------

    /**
     * @param  string|null  $enumShort  nome curto do Enum (para Rule::enum)
     * @return list<string> itens da regra (renderizados como array PHP)
     */
    /**
     * @param  string|null  $tabelaFqcn  FQCN do relacionado, para o `exists`
     * @return list<string>
     */
    public function regras(?string $enumShort = null, ?string $tabelaFqcn = null): array
    {
        $obrig = $this->nullable ? "'nullable'" : "'required'";

        if ($this->ehRelacao()) {
            // `exists` e não só `integer`: sem ele, um id de outra empresa
            // passa na validação e a FK só reclama se existir no banco inteiro.
            return [$obrig, "'integer'", "'exists:{$this->relacaoTabela($tabelaFqcn)},id'"];
        }

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

    /**
     * @param  string|null  $relacaoFqcn  FQCN do model relacionado, quando houver
     */
    public function fragmentoFactory(?string $enumShort = null, ?string $relacaoFqcn = null): string
    {
        if ($this->ehRelacao() && $relacaoFqcn !== null) {
            // A fábrica CRIA o relacionado. Um id inventado passaria no
            // `integer` e morreria na FK — e o teste gerado falharia por um
            // motivo que não tem nada a ver com o que ele testa.
            return "'{$this->nome}' => \\{$relacaoFqcn}::factory(),";
        }

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
    public function valorTeste(?string $relacaoFqcn = null): string
    {
        if ($this->ehRelacao() && $relacaoFqcn !== null) {
            // Cria o relacionado e usa o id dele. Um literal aqui reprovaria no
            // `exists:` — e o teste gerado falharia por causa do próprio valor
            // de exemplo, não do que ele se propõe a verificar.
            return "\\{$relacaoFqcn}::factory()->create()->id";
        }

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

    /**
     * A declaração deste campo na base declarativa: `Campo::tipo('nome', 'Rótulo')->...`.
     *
     * Substitui QUATRO emissões que o gerador fazia em paralelo — a entrada de
     * `fields()`, a `Column::make()`, o `Filter::` e a célula do PDF. Elas
     * divergiam, e a divergência era o defeito: toda coluna saía `searchable()`
     * (inclusive cor hexadecimal e data), dinheiro ia para a tela em centavos
     * crus, booleano renderizava `0`/`1`, e campo numérico ou de data nascia
     * sem filtro nenhum. Nenhum desses era erro de quem gerou: era o que o
     * gerador imprimia.
     *
     * @param  string|null  $enumShort  nome curto do Enum de status, quando aplicável
     */
    public function campoDeclarativo(?string $enumShort = null): string
    {
        $rotulo = $this->label();

        // Campo::relacao já traz o eager load: a base coleta as relações e emite
        // o ->with() sozinha. Era passo manual documentado — e passo manual
        // documentado é fonte de bug documentada, uma consulta N+1 por tela.
        if ($this->ehRelacao()) {
            return sprintf(
                "Campo::relacao('%s', '%s', '%s', '%s')%s,",
                $this->nome,
                $this->label(),
                $this->relacaoMetodo(),
                $this->relacaoAtributo,
                $this->nullable ? '' : '->obrigatorio()',
            );
        }

        $declaracao = match ($this->tipo) {
            'money' => "Campo::dinheiro('{$this->nome}', '{$rotulo}')",
            'integer', 'decimal', 'float' => "Campo::numero('{$this->nome}', '{$rotulo}')",
            'boolean' => "Campo::booleano('{$this->nome}', '{$rotulo}')",
            'date' => "Campo::data('{$this->nome}', '{$rotulo}')",
            'datetime' => "Campo::dataHora('{$this->nome}', '{$rotulo}')",
            'enum' => sprintf("Campo::enum('%s', '%s', %s::class)", $this->nome, $rotulo, $enumShort ?? 'self'),
            default => "Campo::texto('{$this->nome}', '{$rotulo}')",
        };

        // Texto longo, rich text e multiselect não cabem numa célula: a coluna
        // existe e fica no seletor, escondida. Antes elas simplesmente não
        // existiam, e quem quisesse ver a descrição tinha de abrir a ficha.
        if (! $this->ehColunaVisivel()) {
            $declaracao .= '->ocultoPorPadrao()->pesquisavel(false)->filtravel(false)';
        }

        // Cor é um hexadecimal: buscar texto nele é ruído que degrada a busca
        // global da tela inteira.
        if ($this->tipo === 'color') {
            $declaracao .= '->pesquisavel(false)->filtravel(false)';
        }

        if (! $this->nullable) {
            $declaracao .= '->obrigatorio()';
        }

        if ($this->unique) {
            $declaracao .= '->unico()';
        }

        return $declaracao . ',';
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
