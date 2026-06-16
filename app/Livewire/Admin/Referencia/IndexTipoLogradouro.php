<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\TipoLogradouro;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Tipos de logradouro (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Tipos de logradouro')]
class IndexTipoLogradouro extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', TipoLogradouro::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.tipos_logradouro.index-tipos-logradouro', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', TipoLogradouro::class) ?? false,
        ]);
    }
}
