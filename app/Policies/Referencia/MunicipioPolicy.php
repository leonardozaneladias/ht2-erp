<?php

declare(strict_types=1);

namespace App\Policies\Referencia;

use App\Models\Referencia\Municipio;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Policies\Referencia\Concerns\ProtegeRegistroSincronizado;

class MunicipioPolicy
{
    use ProtegeRegistroSincronizado;

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('municipios.listar');
    }

    public function view(AdminUser $auth, Municipio $registro): bool
    {
        return $auth->can('municipios.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('municipios.criar');
    }

    public function update(AdminUser $auth, Municipio $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('municipios.editar');
    }

    public function delete(AdminUser $auth, Municipio $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('municipios.deletar');
    }

    public function restore(AdminUser $auth, Municipio $registro): bool
    {
        return $auth->can('municipios.restaurar');
    }

    public function forceDelete(AdminUser $auth, Municipio $registro): bool
    {
        return $this->editavel($registro)
            && $auth->can('municipios.excluir_permanente');
    }
}
