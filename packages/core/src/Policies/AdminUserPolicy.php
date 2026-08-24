<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\HierarchyResolver;

class AdminUserPolicy
{
    public function __construct(private readonly HierarchyResolver $hierarchy) {}

    public function viewAny(AdminUser $auth): bool
    {
        return $auth->can('usuarios.listar');
    }

    public function view(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.listar');
    }

    public function create(AdminUser $auth): bool
    {
        return $auth->can('usuarios.criar');
    }

    public function update(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.editar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function toggleStatus(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.editar')
            && ! $auth->is($usuario)
            && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function gerenciarAcessos(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('acessos.gerenciar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function gerenciarDoisFatores(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.gerenciar-2fa') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function impersonate(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.impersonar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function exportarDados(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.exportar-dados');
    }

    public function anonimizar(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.anonimizar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function delete(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.deletar')
            && ! $auth->is($usuario)
            && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function restore(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.restaurar') && $this->hierarchy->podeGerir($auth, $usuario);
    }

    public function forceDelete(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.excluir_permanente') && $this->hierarchy->podeGerir($auth, $usuario);
    }
}
