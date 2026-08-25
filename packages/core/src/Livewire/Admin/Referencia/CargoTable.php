<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\RecursoTable;
use HT2ML\Core\Models\Referencia\Cargo;

final class CargoTable extends RecursoTable
{
    protected function model(): string
    {
        return Cargo::class;
    }

    protected function recurso(): string
    {
        return 'cargos';
    }

    protected function rotaBase(): string
    {
        return 'admin.referencia.cargos';
    }

    /**
     * @return list<Campo>
     */
    protected function campos(): array
    {
        return [
            Campo::texto('codigo_cbo', 'Código CBO')->placeholder('Filtrar por código'),
            Campo::texto('descricao', 'Descrição'),
            Campo::booleano('ativo', 'Ativo'),
        ];
    }
}
