<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Facades\Filter;

/**
 * Os widgets de filtro do PowerGrid que a base sabe derivar de um tipo.
 *
 * Existe como enum, e não como string solta, para que `TipoCampo::filtro()`
 * seja exaustivo: um tipo novo sem filtro decidido não compila.
 */
enum FiltroDeCampo
{
    /**
     * @param  array<int|string, string>  $opcoes  usado só por MultiSelecao
     * @param  array{0: string, 1: string}  $rotulosBooleanos
     */
    public function construir(
        string $coluna,
        string $placeholder,
        array $opcoes = [],
        array $rotulosBooleanos = ['Sim', 'Não'],
    ): FilterBase {
        return match ($this) {
            self::Texto => Filter::inputText($coluna)->placeholder($placeholder),
            self::Numero => Filter::number($coluna)->thousands('.')->decimal(','),
            self::Data => Filter::datepicker($coluna),
            self::DataHora => Filter::datetimepicker($coluna),
            // ->label() não é enfeite: o default do PowerGrid é 'Yes'/'No', e
            // seis tabelas do repositório mostravam isso a usuários brasileiros.
            self::Booleano => Filter::boolean($coluna)->label(...$rotulosBooleanos),
            self::MultiSelecao => Filter::multiSelect($coluna)
                ->dataSource(array_map(
                    static fn (int|string $valor, string $rotulo): array => ['id' => $valor, 'name' => $rotulo],
                    array_keys($opcoes),
                    array_values($opcoes),
                ))
                ->optionValue('id')
                ->optionLabel('name'),
        };
    }
    case Texto;
    case Numero;
    case Data;
    case DataHora;
    case Booleano;
    case MultiSelecao;
}
