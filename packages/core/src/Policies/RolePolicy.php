<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\HierarchyResolver;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function __construct(private readonly HierarchyResolver $hierarchy) {}

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
        return $auth->can('perfis.gerenciar')
            && ! $this->ehProtegida($role)
            && $this->hierarchy->podeGerirRole($auth, $role);
    }

    public function delete(AdminUser $auth, Role $role): bool
    {
        return $auth->can('perfis.gerenciar')
            && ! $this->ehProtegida($role)
            && $this->hierarchy->podeGerirRole($auth, $role);
    }

    private function ehProtegida(Role $role): bool
    {
        /** @var list<string> $protegidas */
        $protegidas = config('access.protected_roles', []);

        return in_array($role->name, $protegidas, true);
    }
}
