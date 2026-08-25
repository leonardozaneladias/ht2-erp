<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\RecursoTable;
use HT2ML\Core\Models\Referencia\Pais;

final class PaisTable extends RecursoTable
{
    protected function model(): string
    {
        return Pais::class;
    }

    protected function recurso(): string
    {
        return 'paises';
    }

    protected function rotaBase(): string
    {
        return 'admin.referencia.paises';
    }

    /**
     * @return list<Campo>
     */
    protected function campos(): array
    {
        return [
            Campo::texto('codigo_iso2', 'Código ISO2'),
            Campo::texto('codigo_iso3', 'Código ISO3'),
            Campo::texto('codigo_numerico', 'Código numérico'),
            Campo::texto('nome', 'Nome'),
            Campo::booleano('ativo', 'Ativo'),
        ];
    }
}
