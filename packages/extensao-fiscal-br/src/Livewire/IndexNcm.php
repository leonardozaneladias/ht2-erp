<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Livewire;

use HT2ML\Core\Livewire\Grid\RecursoIndex;
use HT2ML\FiscalBr\Models\Ncm;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Listagem de NCMs (invólucro fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('NCMs')]
class IndexNcm extends RecursoIndex
{
    /**
     * @return class-string<Ncm>
     */
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

    protected function view(): string
    {
        return 'fiscal-br::ncms.index-ncms';
    }
}
