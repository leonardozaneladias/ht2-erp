<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\RecursoIndex;
use HT2ML\Core\Models\Referencia\Banco;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Listagem de Bancos (invólucro fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Bancos')]
class IndexBanco extends RecursoIndex
{
    /**
     * @return class-string<Banco>
     */
    protected function model(): string
    {
        return Banco::class;
    }

    protected function recurso(): string
    {
        return 'bancos';
    }

    protected function rotaBase(): string
    {
        return 'admin.referencia.bancos';
    }

    protected function view(): string
    {
        return 'livewire.admin.referencia.bancos.index-bancos';
    }
}
