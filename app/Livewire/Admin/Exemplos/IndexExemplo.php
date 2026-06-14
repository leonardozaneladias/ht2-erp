<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exemplos;

use App\Models\Exemplo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Exemplos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Exemplos')]
class IndexExemplo extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Exemplo::class);
    }

    public function render(): View
    {
        return view('livewire.admin.exemplos.index-exemplos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Exemplo::class) ?? false,
        ]);
    }
}
