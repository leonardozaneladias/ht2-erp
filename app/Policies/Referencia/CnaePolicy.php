<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Cnae;

class CnaePolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('cnaes.listar');
    }

    public function view(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('cnaes.criar');
    }

    public function update(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.editar');
    }

    public function delete(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.deletar');
    }

    public function restore(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.restaurar');
    }

    public function forceDelete(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.excluir_permanente');
    }
}
