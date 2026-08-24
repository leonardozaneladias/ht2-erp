<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies\Referencia;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class TipoLogradouroPolicy
{
    use ProtegeRegistroSincronizado;

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
        return $this->editavel($registro)
            && $auth->can('tipos_logradouro.editar');
    }

    public function delete(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('tipos_logradouro.deletar');
    }

    public function restore(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $auth->can('tipos_logradouro.restaurar');
    }

    public function forceDelete(AdminUser $auth, TipoLogradouro $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('tipos_logradouro.excluir_permanente');
    }
}
