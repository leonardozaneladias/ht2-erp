<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Policies;

use HT2ML\Core\Models\AdminUser;
use HT2ML\ExemploDemo\Models\Exemplo;

class ExemploPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('exemplos.listar');
    }

    public function view(AdminUser $auth, Exemplo $registro): bool
    {
        return $auth->can('exemplos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('exemplos.criar');
    }

    public function update(AdminUser $auth, Exemplo $registro): bool
    {
        return $auth->can('exemplos.editar');
    }

    public function delete(AdminUser $auth, Exemplo $registro): bool
    {
        return $auth->can('exemplos.deletar');
    }

    public function restore(AdminUser $auth, Exemplo $registro): bool
    {
        return $auth->can('exemplos.restaurar');
    }

    public function forceDelete(AdminUser $auth, Exemplo $registro): bool
    {
        return $auth->can('exemplos.excluir_permanente');
    }
}
