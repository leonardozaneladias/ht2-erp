<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;

/**
 * Uma declaração de campo, e as cinco derivações que saem dela.
 *
 * O componente declara UMA lista de campos; a base produz `fields()`,
 * `columns()`, `filters()`, o mapeamento de exportação e as regras de
 * validação. Antes eram quatro listas paralelas por tela, mantidas em sincronia
 * por disciplina — e a divergência não aparecia como erro, aparecia como
 * booleano renderizando `0` e coluna numérica sem filtro.
 *
 * O objeto é MUTÁVEL de propósito: `Campo::texto(...)->obrigatorio()->max(120)`
 * é a forma que se lê melhor num array literal, e o campo vive só o tempo de
 * montar a grid. Um VO imutável aqui multiplicaria alocações sem ganhar nada.
 *
 * A configurabilidade cresce por MÉTODO, não por ramo de gerador. "Select com
 * pesquisa" combinado com "múltiplo" e "obrigatório" são oito ramos de stub e
 * três chamadas encadeadas aqui — é a razão de o gerador ter 2.085 linhas e
 * ainda assim não saber emitir relacionamento, upload nem filtro de data.
 */
final class Campo
{
    private bool $pesquisavel;

    private bool $ordenavel = true;

    private bool $filtravel = true;

    private bool $ocultoPorPadrao = false;

    private bool $naExportacao = true;

    private ?string $largura = null;

    private ?string $alinhamento;

    private bool $somar = false;

    private ?string $placeholderFiltro = null;

    private ?FilterBase $filtroCustomizado = null;

    /** @var array{0: string, 1: string} rótulos do filtro booleano */
    private array $rotulosBooleanos = ['Sim', 'Não'];

    /** @var Closure(Column): Column|null */
    private ?Closure $ajusteDeColuna = null;

    /** @var Closure(Model): string|null */
    private ?Closure $formatador = null;

    /** @var Closure(Model): string|null */
    private ?Closure $formatadorDeExportacao = null;

    /** @var list<ValidationRule|string> */
    private array $regras = [];

    private bool $obrigatorio = false;

    private function __construct(
        public readonly TipoCampo $tipo,
        public readonly string $nome,
        public readonly string $rotulo,
        /** Relação Eloquent a carregar com eager load — só em TipoCampo::Relacao. */
        public readonly ?string $relacao = null,
        /** Atributo da relação a exibir, ou classe do enum em TipoCampo::Enum. */
        public readonly ?string $referencia = null,
    ) {
        $this->pesquisavel = $tipo->pesquisavelPorPadrao();
        $this->alinhamento = $tipo->alinhamento();
    }

    // ------------------------------------------------------------------ tipos

    public static function texto(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::Texto, $nome, $rotulo);
    }

    public static function numero(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::Numero, $nome, $rotulo);
    }

    /** Valor em centavos (ADR-0014); a apresentação vira R$ 1.234,56. */
    public static function dinheiro(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::Dinheiro, $nome, $rotulo);
    }

    public static function data(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::Data, $nome, $rotulo);
    }

    public static function dataHora(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::DataHora, $nome, $rotulo);
    }

    public static function booleano(string $nome, string $rotulo, string $sim = 'Sim', string $nao = 'Não'): self
    {
        $campo = new self(TipoCampo::Booleano, $nome, $rotulo);
        $campo->formatador = static fn (Model $linha): string => $linha->getAttribute($nome) ? $sim : $nao;
        // Os mesmos rótulos da célula vão para o filtro. Declarar 'Ativa'/
        // 'Inativa' e o dropdown dizer outra coisa é a divergência que as
        // quatro listas paralelas produziam.
        $campo->rotulosBooleanos = [$sim, $nao];

        return $campo;
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     */
    public static function enum(string $nome, string $rotulo, string $enum): self
    {
        return new self(TipoCampo::Enum, $nome, $rotulo, referencia: $enum);
    }

    /**
     * Chave estrangeira exibida pelo atributo da relação.
     *
     * O eager load é derivado daqui pela base. Hoje docs/criar-modulo.md manda
     * o desenvolvedor lembrar do `->with()` à mão — passo manual documentado é
     * fonte de bug documentada, e em vinte telas com FK é a diferença entre
     * vinte N+1 e nenhum.
     */
    public static function relacao(string $nome, string $rotulo, string $relacao, string $atributo = 'nome'): self
    {
        return new self(TipoCampo::Relacao, $nome, $rotulo, relacao: $relacao, referencia: $atributo);
    }

    public static function arquivo(string $nome, string $rotulo): self
    {
        return new self(TipoCampo::Arquivo, $nome, $rotulo);
    }

    /**
     * Fuga de nível 1: a apresentação é sua, o resto da base continua valendo.
     *
     * @param  Closure(Model): string  $render
     */
    public static function personalizado(string $nome, string $rotulo, Closure $render): self
    {
        $campo = new self(TipoCampo::Personalizado, $nome, $rotulo);
        $campo->formatador = $render;

        return $campo;
    }

    // ------------------------------------------------------- apresentação

    public function pesquisavel(bool $valor = true): self
    {
        $this->pesquisavel = $valor;

        return $this;
    }

    public function ordenavel(bool $valor = true): self
    {
        $this->ordenavel = $valor;

        return $this;
    }

    public function filtravel(bool $valor = true): self
    {
        $this->filtravel = $valor;

        return $this;
    }

    /** Coluna disponível no seletor, escondida até alguém pedir. */
    public function ocultoPorPadrao(): self
    {
        $this->ocultoPorPadrao = true;

        return $this;
    }

    public function foraDaExportacao(): self
    {
        $this->naExportacao = false;

        return $this;
    }

    public function largura(string $css): self
    {
        $this->largura = $css;

        return $this;
    }

    public function alinhado(string $classe): self
    {
        $this->alinhamento = $classe;

        return $this;
    }

    /** Entra no rodapé de somatório (`summarize`). */
    public function somar(): self
    {
        $this->somar = true;

        return $this;
    }

    public function placeholder(string $texto): self
    {
        $this->placeholderFiltro = $texto;

        return $this;
    }

    // --------------------------------------------------------- validação

    public function obrigatorio(bool $valor = true): self
    {
        $this->obrigatorio = $valor;

        return $this;
    }

    public function max(int $tamanho): self
    {
        return $this->regra("max:{$tamanho}");
    }

    public function min(int $tamanho): self
    {
        return $this->regra("min:{$tamanho}");
    }

    public function unico(?string $tabela = null): self
    {
        return $this->regra($tabela === null ? 'unique' : "unique:{$tabela},{$this->nome}");
    }

    /**
     * Aceita ValidationRule, string ou closure — é o que permite
     * `->regra(new Cpf())` e `->regra(new MatriculaValida($escola))`.
     *
     * As classes de regra já existiam em HT2ML\Core\Rules; o que faltava era a
     * costura entre elas e a declaração do campo.
     */
    public function regra(ValidationRule|string $regra): self
    {
        $this->regras[] = $regra;

        return $this;
    }

    /**
     * @param  list<ValidationRule|string>  $regras
     */
    public function regras(array $regras): self
    {
        foreach ($regras as $regra) {
            $this->regra($regra);
        }

        return $this;
    }

    // ---------------------------------------------------- fugas de nível 1

    public function comFiltro(FilterBase $filtro): self
    {
        $this->filtroCustomizado = $filtro;

        return $this;
    }

    /**
     * @param  Closure(Column): Column  $ajuste
     */
    public function comColuna(Closure $ajuste): self
    {
        $this->ajusteDeColuna = $ajuste;

        return $this;
    }

    /**
     * @param  Closure(Model): string  $texto
     */
    public function paraExportar(Closure $texto): self
    {
        $this->formatadorDeExportacao = $texto;

        return $this;
    }

    // ------------------------------------------------------------ derivações

    /** O nome do campo em `fields()`: derivado quando o tipo usa rótulo. */
    public function campoDeExibicao(): string
    {
        return $this->tipo->usaRotulo() ? "{$this->nome}_label" : $this->nome;
    }

    /**
     * O valor que a célula mostra. Devolve null quando não há transformação —
     * o PowerGrid usa o atributo cru.
     *
     * @return Closure(Model): string|null
     */
    public function formatador(): ?Closure
    {
        if ($this->formatador !== null) {
            return $this->formatador;
        }

        return match ($this->tipo) {
            TipoCampo::Dinheiro => fn (Model $linha): string => 'R$ ' . number_format(
                ((int) $linha->getAttribute($this->nome)) / 100,
                2,
                ',',
                '.',
            ),
            TipoCampo::Data => fn (Model $linha): string => $linha->getAttribute($this->nome)?->format('d/m/Y') ?? '',
            TipoCampo::DataHora => fn (Model $linha): string => $linha->getAttribute($this->nome)?->format('d/m/Y H:i') ?? '',
            // Enum vira badge quando o caso sabe sua cor. Sem isto a coluna
            // mostraria a string crua do banco ('em_analise'), que era o
            // defeito nº 1 das telas geradas — e com ->label() sem cor a
            // informação de status perde a leitura periférica que o badge dá.
            TipoCampo::Enum => fn (Model $linha): string => $this->comoBadge($linha->getAttribute($this->nome)),
            TipoCampo::Relacao => fn (Model $linha): string => (string) (
                $linha->getRelationValue((string) $this->relacao)?->getAttribute((string) $this->referencia) ?? ''
            ),
            default => null,
        };
    }

    /** O texto que vai para XLS/CSV/PDF — sem HTML, sem badge. */
    public function textoDeExportacao(Model $linha): string
    {
        if ($this->formatadorDeExportacao !== null) {
            return ($this->formatadorDeExportacao)($linha);
        }

        // Enum é o único tipo cuja apresentação na TELA é HTML. Mandar o markup
        // do badge para dentro de uma célula de XLS seria entregar
        // '<x-shared.badge…>' a quem abrisse a planilha.
        if ($this->tipo === TipoCampo::Enum) {
            return $this->rotuloDoEnum($linha->getAttribute($this->nome));
        }

        $formatador = $this->formatador();

        return $formatador === null
            ? (string) $linha->getAttribute($this->nome)
            : $formatador($linha);
    }

    public function exportavel(): bool
    {
        return $this->naExportacao;
    }

    public function somavel(): bool
    {
        return $this->somar;
    }

    /** A relação a carregar com eager load, ou null. */
    public function eagerLoad(): ?string
    {
        return $this->relacao;
    }

    public function coluna(): Column
    {
        $coluna = Column::make($this->rotulo, $this->campoDeExibicao(), $this->nome);

        if ($this->pesquisavel) {
            $coluna->searchable();
        }

        if ($this->ordenavel) {
            $coluna->sortable();
        }

        if ($this->ocultoPorPadrao) {
            // hidden(true, false): escondida, mas NÃO forçada — continua
            // disponível no seletor de colunas.
            $coluna->hidden(true, false);
        }

        $coluna->visibleInExport($this->naExportacao);

        $classe = (string) $this->alinhamento;
        $estilo = $this->largura === null ? '' : "width: {$this->largura}";

        if ($classe !== '' || $estilo !== '') {
            // Cabeçalho E corpo: alinhar só as células deixa o título "Preço"
            // à esquerda e os valores à direita, que é pior que não alinhar. A
            // largura vai no <th>, que é quem a tabela respeita.
            $coluna->headerAttribute($classe, $estilo);
            $coluna->bodyAttribute($classe);
        }

        return $this->ajusteDeColuna === null ? $coluna : ($this->ajusteDeColuna)($coluna);
    }

    /**
     * O filtro do campo, ou null quando não há um derivável nem declarado.
     *
     * @param  array<int|string, string>  $opcoes  para MultiSelecao
     */
    public function filtro(array $opcoes = []): ?FilterBase
    {
        if ($this->filtroCustomizado !== null) {
            return $this->filtroCustomizado;
        }

        if (! $this->filtravel) {
            return null;
        }

        $filtro = $this->tipo->filtro();

        if ($filtro === null || ($filtro === FiltroDeCampo::MultiSelecao && $opcoes === [])) {
            return null;
        }

        return $filtro->construir(
            $this->nome,
            $this->placeholderFiltro ?? $this->placeholderPadrao(),
            $opcoes,
            $this->rotulosBooleanos,
        );
    }

    /**
     * Regras de validação do campo, com `required`/`nullable` na frente.
     *
     * AINDA SEM CONSUMIDOR EM PRODUÇÃO: quem valida hoje é `{Recurso}Rules`, e
     * assim continua até existir um `RecursoForm`. O contrato está fixado em
     * tests/Unit/Grid/CampoTest.php para que a costura, quando vier, não
     * precise redescobrir o formato — e para que isto não seja código sem
     * nenhuma prova.
     *
     * @return list<ValidationRule|string>
     */
    public function regrasDeValidacao(): array
    {
        return [
            $this->obrigatorio ? 'required' : 'nullable',
            ...$this->tipo->regrasBase(),
            ...$this->regras,
        ];
    }

    /**
     * O badge do caso do enum, ou o rótulo puro quando o enum não declara cor.
     */
    private function comoBadge(mixed $valor): string
    {
        $rotulo = $this->rotuloDoEnum($valor);

        if (! $valor instanceof BackedEnum || ! method_exists($valor, 'variant')) {
            return $rotulo;
        }

        return Blade::render(
            '<x-shared.badge :variant="$v" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $valor->variant(), 't' => $rotulo],
        );
    }

    private function rotuloDoEnum(mixed $valor): string
    {
        if (! $valor instanceof BackedEnum) {
            return (string) $valor;
        }

        return method_exists($valor, 'label') ? (string) $valor->label() : (string) $valor->value;
    }

    /**
     * "Filtrar por código ISO2", não "Filtrar por Código ISO2" nem "por código
     * iso2": só a primeira palavra desce de caixa, e apenas quando não é uma
     * sigla. Sem isto, "ISPB" viraria "iSPB".
     */
    private function placeholderPadrao(): string
    {
        $primeira = Str::before($this->rotulo, ' ');
        $resto = Str::after($this->rotulo, ' ');

        if ($primeira !== Str::upper($primeira)) {
            $primeira = Str::lcfirst($primeira);
        }

        return 'Filtrar por ' . trim($primeira . ($resto === $this->rotulo ? '' : " {$resto}"));
    }
}
