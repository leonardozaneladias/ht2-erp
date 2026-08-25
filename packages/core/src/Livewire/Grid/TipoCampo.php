<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

/**
 * O tipo de um {@see Campo}, e as cinco decisões que ele resolve de uma vez.
 *
 * Uma tabela PowerGrid escrita à mão mantém QUATRO listas paralelas sobre o
 * mesmo conjunto de campos — `fields()`, `columns()`, `filters()` e o
 * mapeamento de exportação — sem nada ligando-as. Vinte telas dão oitenta
 * listas a não deixar divergir por disciplina humana, e a divergência não
 * aparece como erro: aparece como booleano renderizando `0`, dinheiro em
 * centavos crus, busca textual numa cor hexadecimal e coluna numérica sem
 * filtro nenhum. Todos esses estavam no repositório, e todos eram GERADOS.
 *
 * Aqui cada tipo decide sozinho como a coluna se apresenta, que filtro aparece,
 * como o valor é formatado na tela e na exportação, e qual é a validação de
 * base. Não é conveniência: é o que faz esses defeitos deixarem de ser
 * possíveis, em vez de deixarem de acontecer.
 */
enum TipoCampo: string
{
    /**
     * Classe CSS de alinhamento do corpo da coluna, ou null para o padrão.
     *
     * Número e dinheiro à direita porque a comparação visual entre linhas é o
     * que se faz com eles: alinhados à esquerda, "9,90" e "1.200,00" ficam com
     * a vírgula em colunas diferentes e o olho não consegue ordenar.
     */
    public function alinhamento(): ?string
    {
        return match ($this) {
            self::Numero, self::Dinheiro => 'text-right',
            self::Booleano => 'text-center',
            default => null,
        };
    }

    /**
     * O filtro que este tipo pede quando o campo é filtrável.
     *
     * `null` significa "nenhum derivável" — use `Campo::comFiltro()`.
     */
    public function filtro(): ?FiltroDeCampo
    {
        return match ($this) {
            self::Texto => FiltroDeCampo::Texto,
            self::Numero, self::Dinheiro => FiltroDeCampo::Numero,
            self::Data => FiltroDeCampo::Data,
            self::DataHora => FiltroDeCampo::DataHora,
            self::Booleano => FiltroDeCampo::Booleano,
            self::Enum, self::Relacao => FiltroDeCampo::MultiSelecao,
            self::Arquivo, self::Personalizado => null,
        };
    }

    /**
     * Busca global faz sentido neste tipo?
     *
     * Só em texto. `searchable()` num campo de cor hexadecimal, de id ou de
     * data é ruído que degrada toda busca da tela — e era o que o gerador
     * emitia por padrão.
     */
    public function pesquisavelPorPadrao(): bool
    {
        return $this === self::Texto;
    }

    /**
     * A coluna exibe um campo derivado (`{nome}_label`) em vez do valor cru?
     *
     * Booleano e enum precisam: `0`/`1` e `'ativo'` não são o que o usuário
     * lê. O `dataField` da coluna continua sendo a coluna real, para que
     * ordenação e filtro batam no banco, não no rótulo.
     */
    public function usaRotulo(): bool
    {
        return match ($this) {
            self::Booleano, self::Enum, self::Relacao, self::Dinheiro, self::Data, self::DataHora => true,
            default => false,
        };
    }

    /**
     * Regras de validação que o tipo implica, antes de qualquer `->regra()`.
     *
     * @return list<string>
     */
    public function regrasBase(): array
    {
        return match ($this) {
            self::Texto => ['string'],
            self::Numero => ['numeric'],
            self::Dinheiro => ['integer'],       // centavos (ADR-0014)
            self::Data => ['date'],
            self::DataHora => ['date'],
            self::Booleano => ['boolean'],
            self::Arquivo => ['file'],
            self::Enum, self::Relacao, self::Personalizado => [],
        };
    }
    case Texto = 'texto';
    case Numero = 'numero';
    case Dinheiro = 'dinheiro';
    case Data = 'data';
    case DataHora = 'dataHora';
    case Booleano = 'booleano';
    case Enum = 'enum';
    case Relacao = 'relacao';
    case Arquivo = 'arquivo';
    case Personalizado = 'personalizado';
}
