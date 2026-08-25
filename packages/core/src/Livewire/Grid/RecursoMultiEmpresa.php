<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

use HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

/**
 * Liga a dimensão multiempresa a uma {@see RecursoTable} — as seis composições
 * de uma vez.
 *
 * Antes, uma tabela multiempresa precisava lembrar de SEIS chamadas espalhadas:
 * `aplicarEscopoMultiEmpresa()` no datasource, `camposMultiEmpresa()` nos
 * fields, `colunasMultiEmpresa()` e `filtrosMultiEmpresa()` no começo dos
 * arrays, e `cabecalhosMultiEmpresa()`/`linhaMultiEmpresa()` na exportação. O
 * gerador emitia as seis por interpolação de placeholder, e esquecer só a
 * primeira — a do escopo — produz uma tela que **passa em todos os testes de um
 * tenant só** e vaza linhas de outras empresas no dia em que a segunda for
 * cadastrada.
 *
 * Aqui é tudo ou nada: `use RecursoMultiEmpresa;` traz as seis.
 *
 * @mixin RecursoTable
 */
trait RecursoMultiEmpresa
{
    use FiltraPorMultiEmpresa;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function escopoDaTela(Builder $query): Builder
    {
        return $this->aplicarEscopoMultiEmpresa($query);
    }

    protected function camposDaTela(PowerGridFields $fields): PowerGridFields
    {
        return $this->camposMultiEmpresa($fields);
    }

    /**
     * @return list<Column>
     */
    protected function colunasIniciais(): array
    {
        return array_values($this->colunasMultiEmpresa());
    }

    /**
     * @return list<FilterBase>
     */
    protected function filtrosIniciais(): array
    {
        return array_values($this->filtrosMultiEmpresa());
    }

    /**
     * @return list<string>
     */
    protected function cabecalhosIniciais(): array
    {
        return array_values($this->cabecalhosMultiEmpresa());
    }

    /**
     * @return list<string>
     */
    protected function celulasIniciais(Model $linha): array
    {
        return array_values($this->linhaMultiEmpresa($linha));
    }
}
