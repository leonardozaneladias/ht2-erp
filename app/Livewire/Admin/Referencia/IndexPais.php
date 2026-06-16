<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Pais;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Países (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Países')]
class IndexPais extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Pais::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.paises.index-paises', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Pais::class) ?? false,
        ]);
    }
}
