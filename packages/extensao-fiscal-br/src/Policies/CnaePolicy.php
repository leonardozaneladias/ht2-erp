<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Policies;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;
use HT2ML\FiscalBr\Models\Cnae;

class CnaePolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('cnaes.listar');
    }

    public function view(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('cnaes.criar');
    }

    public function update(AdminUser $auth, Cnae $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cnaes.editar');
    }

    public function delete(AdminUser $auth, Cnae $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cnaes.deletar');
    }

    public function restore(AdminUser $auth, Cnae $registro): bool
    {
        return $auth->can('cnaes.restaurar');
    }

    public function forceDelete(AdminUser $auth, Cnae $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('cnaes.excluir_permanente');
    }
}
