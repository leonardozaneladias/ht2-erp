<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\Referencia\Cargo;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class CargoPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('cargos.listar');
    }

    public function view(AdminUser $auth, Cargo $registro): bool
    {
        return $auth->can('cargos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('cargos.criar');
    }

    public function update(AdminUser $auth, Cargo $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cargos.editar');
    }

    public function delete(AdminUser $auth, Cargo $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cargos.deletar');
    }

    public function restore(AdminUser $auth, Cargo $registro): bool
    {
        return $auth->can('cargos.restaurar');
    }

    public function forceDelete(AdminUser $auth, Cargo $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cargos.excluir_permanente');
    }
}
