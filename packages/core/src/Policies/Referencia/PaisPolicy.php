<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies\Referencia;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Pais;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class PaisPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('paises.listar');
    }

    public function view(AdminUser $auth, Pais $registro): bool
    {
        return $auth->can('paises.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('paises.criar');
    }

    public function update(AdminUser $auth, Pais $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('paises.editar');
    }

    public function delete(AdminUser $auth, Pais $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('paises.deletar');
    }

    public function restore(AdminUser $auth, Pais $registro): bool
    {
        return $auth->can('paises.restaurar');
    }

    public function forceDelete(AdminUser $auth, Pais $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('paises.excluir_permanente');
    }
}
