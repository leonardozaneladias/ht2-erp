<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela de listagem de usuários admin.
 *
 * Wrapper fino: renderiza o cabeçalho de página e embute o grid PowerGrid
 * (App\Livewire\Admin\Usuarios\UsuariosTable), que cuida de busca, filtros,
 * ordenação, paginação, ações em massa e export.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Usuários admin')]
class IndexUsuarios extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', AdminUser::class);
    }

    public function render(): View
    {
        return view('livewire.admin.usuarios.index-usuarios', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', AdminUser::class) ?? false,
        ]);
    }
}
