<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Livewire\Funcionarios;

use HT2ERP\Rh\Models\Funcionario;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Funcionarios (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Funcionarios')]
class IndexFuncionario extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Funcionario::class);
    }

    public function render(): View
    {
        return view('rh::livewire.funcionarios.index-funcionarios', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Funcionario::class) ?? false,
        ]);
    }
}
