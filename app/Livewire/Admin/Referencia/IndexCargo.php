<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Cargo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Cargos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Cargos')]
class IndexCargo extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Cargo::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cargos.index-cargos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Cargo::class) ?? false,
        ]);
    }
}
