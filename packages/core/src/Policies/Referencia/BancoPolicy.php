<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies\Referencia;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Banco;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class BancoPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('bancos.listar');
    }

    public function view(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('bancos.criar');
    }

    public function update(AdminUser $auth, Banco $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('bancos.editar');
    }

    public function delete(AdminUser $auth, Banco $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('bancos.deletar');
    }

    public function restore(AdminUser $auth, Banco $registro): bool
    {
        return $auth->can('bancos.restaurar');
    }

    public function forceDelete(AdminUser $auth, Banco $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('bancos.excluir_permanente');
    }
}
