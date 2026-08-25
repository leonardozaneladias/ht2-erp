<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\RecursoIndex;
use HT2ML\Core\Models\Referencia\Cargo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Listagem de Cargos (invólucro fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Cargos')]
class IndexCargo extends RecursoIndex
{
    /**
     * @return class-string<Cargo>
     */
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

    protected function view(): string
    {
        return 'livewire.admin.referencia.cargos.index-cargos';
    }
}
