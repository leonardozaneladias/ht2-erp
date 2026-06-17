<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Policies;

use App\Models\AdminUser;
use HT2ERP\Rh\Models\Funcionario;

class FuncionarioPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('rh.funcionarios.listar');
    }

    public function view(AdminUser $auth, Funcionario $registro): bool
    {
        return $auth->can('rh.funcionarios.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('rh.funcionarios.criar');
    }

    public function update(AdminUser $auth, Funcionario $registro): bool
    {
        return $auth->can('rh.funcionarios.editar');
    }

    public function delete(AdminUser $auth, Funcionario $registro): bool
    {
        return $auth->can('rh.funcionarios.deletar');
    }

    public function restore(AdminUser $auth, Funcionario $registro): bool
    {
        return $auth->can('rh.funcionarios.restaurar');
    }

    public function forceDelete(AdminUser $auth, Funcionario $registro): bool
    {
        return $auth->can('rh.funcionarios.excluir_permanente');
    }
}
