<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Estado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Estados (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Estados')]
class IndexEstado extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Estado::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.estados.index-estados', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Estado::class) ?? false,
        ]);
    }
}
