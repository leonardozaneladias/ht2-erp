<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use App\Actions\Admin\DefinirPerfilAtivoAction;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SeletorPerfilAtivo extends Component
{
    public function definir(?int $roleId, DefinirPerfilAtivoAction $action): void
    {
        $user = Auth::guard('admin')->user();

        if (! $user instanceof AdminUser) {
            return;
        }

        $action->execute($user, $roleId);

        $this->redirect(request()->header('Referer') ?? route('admin.dashboard'), navigate: true);
    }

    public function render(): View
    {
        $user = Auth::guard('admin')->user();

        $roles = $user instanceof AdminUser
            ? $user->roles()->orderBy('name')->get()
            : collect();

        $perfilAtivoId = is_numeric(session('admin.perfil_ativo_role_id'))
            ? (int) session('admin.perfil_ativo_role_id')
            : null;

        $perfilAtivoNome = $perfilAtivoId !== null
            ? $roles->firstWhere('id', $perfilAtivoId)?->name
            : null;

        return view('livewire.admin.acesso.seletor-perfil-ativo', [
            'roles' => $roles,
            'perfilAtivoId' => $perfilAtivoId,
            'perfilAtivoNome' => $perfilAtivoNome,
        ]);
    }
}
