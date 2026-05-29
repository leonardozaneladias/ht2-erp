<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('perfis.listar');
    }

    public function view(AdminUser $auth, Role $role): bool
    {
        return $auth->can('perfis.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('perfis.gerenciar');
    }

    public function update(AdminUser $auth, Role $role): bool
    {
        return $auth->can('perfis.gerenciar');
    }

    public function delete(AdminUser $auth, Role $role): bool
    {
        if ($role->name === 'super-admin') {
            return false;
        }

        return $auth->can('perfis.gerenciar');
    }
}
