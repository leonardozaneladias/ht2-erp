<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;

class AdminUserPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('usuarios.listar');
    }

    public function view(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('usuarios.criar');
    }

    public function update(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.editar');
    }

    public function toggleStatus(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.editar') && ! $auth->is($usuario);
    }
}
