<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\AdminUser;
use App\Models\Referencia\Moeda;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class MoedaPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('moedas.listar');
    }

    public function view(AdminUser $auth, Moeda $registro): bool
    {
        return $auth->can('moedas.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('moedas.criar');
    }

    public function update(AdminUser $auth, Moeda $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('moedas.editar');
    }

    public function delete(AdminUser $auth, Moeda $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('moedas.deletar');
    }

    public function restore(AdminUser $auth, Moeda $registro): bool
    {
        return $auth->can('moedas.restaurar');
    }

    public function forceDelete(AdminUser $auth, Moeda $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('moedas.excluir_permanente');
    }
}
