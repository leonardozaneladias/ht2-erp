<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Livewire;

use HT2ML\Core\Livewire\Grid\Campo;
use HT2ML\Core\Livewire\Grid\RecursoTable;
use HT2ML\FiscalBr\Models\Ncm;

final class NcmTable extends RecursoTable
{
    protected function model(): string
    {
        return Ncm::class;
    }

    protected function recurso(): string
    {
        return 'ncms';
    }

    protected function rotaBase(): string
    {
        return 'admin.referencia.ncms';
    }

    /**
     * @return list<Campo>
     */
    protected function campos(): array
    {
        return [
            Campo::texto('codigo', 'Código'),
            Campo::texto('descricao', 'Descrição'),
        ];
    }
}
