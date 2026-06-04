<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Empresas;

use App\Models\Empresa;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de empresas (wrapper fino sobre o grid PowerGrid EmpresasTable).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Empresas')]
class IndexEmpresas extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Empresa::class);
    }

    public function render(): View
    {
        return view('livewire.admin.empresas.index-empresas', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Empresa::class) ?? false,
        ]);
    }
}
