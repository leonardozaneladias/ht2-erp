<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Hierarquia de perfis por nível. Maior nível = mais poder.
 * Um ator só gere usuários/perfis de nível estritamente abaixo do seu.
 */
final class HierarchyResolver
{
    /**
     * Nível efetivo do usuário = maior nível entre suas roles.
     * Super-admin é o topo absoluto.
     */
    public function nivelDe(AdminUser $user): int
    {
        if ($this->ehSuperAdmin($user)) {
            return PHP_INT_MAX;
        }

        $user->loadMissing('roles');

        $niveis = $user->roles->pluck('nivel');

        return $niveis->isEmpty() ? 0 : (int) $niveis->max();
    }

    public function podeGerir(AdminUser $ator, AdminUser $alvo): bool
    {
        if ($this->ehSuperAdmin($ator)) {
            return true;
        }

        return $this->nivelDe($ator) > $this->nivelDe($alvo);
    }

    public function podeGerirRole(AdminUser $ator, Role $role): bool
    {
        if ($this->ehSuperAdmin($ator)) {
            return true;
        }

        return $this->nivelDe($ator) > (int) $role->getAttribute('nivel');
    }

    /**
     * Roles que o ator pode atribuir/gerir (nível estritamente abaixo do seu).
     *
     * @return Collection<int, Role>
     */
    public function rolesGerenciaveis(AdminUser $ator): Collection
    {
        $query = Role::query()->where('guard_name', 'admin')->orderBy('nivel');

        if (! $this->ehSuperAdmin($ator)) {
            $query->where('nivel', '<', $this->nivelDe($ator));
        }

        return $query->get();
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains('name', $this->superAdminRole());
    }

    private function superAdminRole(): string
    {
        return (string) config('access.super_admin_role', 'super-admin');
    }
}
