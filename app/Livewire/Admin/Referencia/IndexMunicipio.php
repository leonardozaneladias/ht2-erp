<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Municipio;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Municípios (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Municípios')]
class IndexMunicipio extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Municipio::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.municipios.index-municipios', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Municipio::class) ?? false,
        ]);
    }
}
