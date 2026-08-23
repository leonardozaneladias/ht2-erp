<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Policies;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;
use HT2ML\FiscalBr\Models\Ncm;

class NcmPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('ncms.listar');
    }

    public function view(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('ncms.criar');
    }

    public function update(AdminUser $auth, Ncm $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('ncms.editar');
    }

    public function delete(AdminUser $auth, Ncm $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('ncms.deletar');
    }

    public function restore(AdminUser $auth, Ncm $registro): bool
    {
        return $auth->can('ncms.restaurar');
    }

    public function forceDelete(AdminUser $auth, Ncm $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('ncms.excluir_permanente');
    }
}
