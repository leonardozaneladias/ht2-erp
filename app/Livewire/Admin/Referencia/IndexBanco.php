<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Banco;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Bancos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Bancos')]
class IndexBanco extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Banco::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.bancos.index-bancos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Banco::class) ?? false,
        ]);
    }
}
