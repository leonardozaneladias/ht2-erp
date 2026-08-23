<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies;

use HT2ML\Core\Models\AdminUser;

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

    public function restore(AdminUser $auth): bool
    {
        return $auth->can('empresas.restaurar');
    }

    public function forceDelete(AdminUser $auth): bool
    {
        return $auth->can('empresas.excluir_permanente');
    }

    public function gerenciarAcessos(AdminUser $auth): bool
    {
        return $auth->can('empresas.acessos');
    }
}
