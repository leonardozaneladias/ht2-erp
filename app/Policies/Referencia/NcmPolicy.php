<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Ncm;

class NcmPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('ncms.listar');
    }

    public function view(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('ncms.criar');
    }

    public function update(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.editar');
    }

    public function delete(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.deletar');
    }

    public function restore(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.restaurar');
    }

    public function forceDelete(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.excluir_permanente');
    }
}
