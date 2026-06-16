<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Banco;

class BancoPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('bancos.listar');
    }

    public function view(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('bancos.criar');
    }

    public function update(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.editar');
    }

    public function delete(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.deletar');
    }

    public function restore(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.restaurar');
    }

    public function forceDelete(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.excluir_permanente');
    }
}
