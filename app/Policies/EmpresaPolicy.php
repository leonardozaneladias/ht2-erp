<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;

class EmpresaPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('empresas.listar');
    }

    public function view(AdminUser $auth): bool
    {
        return $auth->can('empresas.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('empresas.criar');
    }

    public function update(AdminUser $auth): bool
    {
        return $auth->can('empresas.editar');
    }

    public function delete(AdminUser $auth): bool
    {
        return $auth->can('empresas.deletar');
    }

    public function gerenciarAcessos(AdminUser $auth): bool
    {
        return $auth->can('empresas.acessos');
    }
}
