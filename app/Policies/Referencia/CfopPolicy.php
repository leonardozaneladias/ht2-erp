<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Cfop;
use App\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class CfopPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('cfops.listar');
    }

    public function view(AdminUser $auth, Cfop $registro): bool
    {
        return $auth->can('cfops.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('cfops.criar');
    }

    public function update(AdminUser $auth, Cfop $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cfops.editar');
    }

    public function delete(AdminUser $auth, Cfop $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cfops.deletar');
    }

    public function restore(AdminUser $auth, Cfop $registro): bool
    {
        return $auth->can('cfops.restaurar');
    }

    public function forceDelete(AdminUser $auth, Cfop $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cfops.excluir_permanente');
    }
}
