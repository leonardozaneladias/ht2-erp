<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Livewire\Departamentos;

use HT2ERP\Rh\Models\Departamento;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Departamentos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Departamentos')]
class IndexDepartamento extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Departamento::class);
    }

    public function render(): View
    {
        return view('rh::livewire.departamentos.index-departamentos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Departamento::class) ?? false,
        ]);
    }
}
