<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Grid\RecursoIndex;
use HT2ML\Core\Models\Referencia\Pais;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

/**
 * Listagem de Países (invólucro fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Países')]
class IndexPais extends RecursoIndex
{
    /**
     * @return class-string<Pais>
     */
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

    protected function view(): string
    {
        return 'livewire.admin.referencia.paises.index-paises';
    }
}
