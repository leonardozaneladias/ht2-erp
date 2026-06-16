<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Moeda;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Moedas (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Moedas')]
class IndexMoeda extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Moeda::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.moedas.index-moedas', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Moeda::class) ?? false,
        ]);
    }
}
