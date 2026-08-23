<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Estado;
use App\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class EstadoPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('estados.listar');
    }

    public function view(AdminUser $auth, Estado $registro): bool
    {
        return $auth->can('estados.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('estados.criar');
    }

    public function update(AdminUser $auth, Estado $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('estados.editar');
    }

    public function delete(AdminUser $auth, Estado $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('estados.deletar');
    }

    public function restore(AdminUser $auth, Estado $registro): bool
    {
        return $auth->can('estados.restaurar');
    }

    public function forceDelete(AdminUser $auth, Estado $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('estados.excluir_permanente');
    }
}
