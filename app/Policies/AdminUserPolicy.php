<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;
use App\Services\Admin\HierarchyResolver;

class AdminUserPolicy
{
    public function __construct(private readonly HierarchyResolver $hierarchy) {}

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
        return $auth->can('usuarios.editar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function toggleStatus(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.editar')
            && ! $auth->is($usuario)
            && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function gerenciarAcessos(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('acessos.gerenciar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function impersonate(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.impersonar') && $this->hierarchy->podeGerir($auth, $usuario);
    }
}
