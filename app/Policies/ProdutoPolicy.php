<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Produto;

class ProdutoPolicy
{
    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('produtos.listar');
    }

    public function view(AdminUser $auth, Produto $registro): bool
    {
        return $auth->can('produtos.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('produtos.criar');
    }

    public function update(AdminUser $auth, Produto $registro): bool
    {
        return $auth->can('produtos.editar');
    }

    public function delete(AdminUser $auth, Produto $registro): bool
    {
        return $auth->can('produtos.deletar');
    }
}
