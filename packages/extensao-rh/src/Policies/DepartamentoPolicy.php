<?php

declare(strict_types=1);

namespace HT2ML\Rh\Policies;

use App\Models\AdminUser;
use HT2ML\Rh\Models\Departamento;

class DepartamentoPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('rh.departamentos.listar');
    }

    public function view(AdminUser $auth, Departamento $registro): bool
    {
        return $auth->can('rh.departamentos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('rh.departamentos.criar');
    }

    public function update(AdminUser $auth, Departamento $registro): bool
    {
        return $auth->can('rh.departamentos.editar');
    }

    public function delete(AdminUser $auth, Departamento $registro): bool
    {
        return $auth->can('rh.departamentos.deletar');
    }

    public function restore(AdminUser $auth, Departamento $registro): bool
    {
        return $auth->can('rh.departamentos.restaurar');
    }

    public function forceDelete(AdminUser $auth, Departamento $registro): bool
    {
        return $auth->can('rh.departamentos.excluir_permanente');
    }
}
