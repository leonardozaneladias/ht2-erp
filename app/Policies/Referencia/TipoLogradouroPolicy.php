<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\TipoLogradouro;

class TipoLogradouroPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('tipos_logradouro.listar');
    }

    public function view(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('tipos_logradouro.criar');
    }

    public function update(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.editar');
    }

    public function delete(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.deletar');
    }

    public function restore(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.restaurar');
    }

    public function forceDelete(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.excluir_permanente');
    }
}
