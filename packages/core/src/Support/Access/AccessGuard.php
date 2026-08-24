<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Access;

use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\HierarchyResolver;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/**
 * Guardas de segurança (defense-in-depth) das operações de acesso:
 * anti-lockout, roles protegidas, hierarquia e imunidade do super-admin.
 */
final class AccessGuard
{
    public function __construct(private readonly HierarchyResolver $hierarchy) {}

    /**
     * Impede que a troca de roles remova o último super-admin ativo.
     *
     * @param  list<string>  $rolesNovas  nomes das roles que o alvo terá após a mudança
     */
    public function garantirNaoEsvaziarSuperAdmins(AdminUser $alvo, array $rolesNovas): void
    {
        $superAdmin = $this->superAdminRole();
        $alvo->loadMissing('roles');

        $eraSuperAdmin = $alvo->roles->contains('name', $superAdmin);
        $continuaSuperAdmin = in_array($superAdmin, $rolesNovas, true);

        if ($eraSuperAdmin && ! $continuaSuperAdmin && $this->contarSuperAdminsAtivos() <= 1) {
            throw AccessException::ultimoSuperAdmin();
        }
    }

    /**
     * Impede desativar o último super-admin ativo.
     */
    public function garantirPodeDesativar(AdminUser $alvo): void
    {
        $alvo->loadMissing('roles');

        if ($alvo->roles->contains('name', $this->superAdminRole()) && $this->contarSuperAdminsAtivos() <= 1) {
            throw AccessException::ultimoSuperAdmin();
        }
    }

    /**
     * Impede o ator de remover de si mesmo as permissões de auto-gestão.
     *
     * @param  list<string>  $rolesNovas
     */
    public function garantirAutoGestao(AdminUser $ator, AdminUser $alvo, array $rolesNovas): void
    {
        if (! $ator->is($alvo)) {
            return;
        }

        $permsNovas = Role::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $rolesNovas)
            ->with('permissions')
            ->get()
            ->flatMap(static fn (Role $role) => $role->permissions->pluck('name'))
            ->unique();

        foreach ($this->selfManagementPermissions() as $permissao) {
            if (! $permsNovas->contains($permissao)) {
                throw AccessException::autoRemocaoGestao();
            }
        }
    }

    /**
     * Auto-gestão no escopo por empresa: o ator não pode remover de si as
     * permissões de auto-gestão considerando seus papéis globais somados aos
     * novos papéis na empresa. Super-admin global nunca se tranca.
     *
     * @param  list<string>  $rolesNovas  nomes dos papéis na empresa após a mudança
     */
    public function garantirAutoGestaoEmpresa(AdminUser $ator, AdminUser $alvo, array $rolesNovas): void
    {
        if (! $ator->is($alvo)) {
            return;
        }

        $ator->loadMissing('roles');

        if ($ator->roles->contains('name', $this->superAdminRole())) {
            return;
        }

        $nomes = array_values(array_unique([
            ...$ator->roles->pluck('name')->all(),
            ...$rolesNovas,
        ]));

        $permsNovas = Role::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $nomes)
            ->with('permissions')
            ->get()
            ->flatMap(static fn (Role $role) => $role->permissions->pluck('name'))
            ->unique();

        foreach ($this->selfManagementPermissions() as $permissao) {
            if (! $permsNovas->contains($permissao)) {
                throw AccessException::autoRemocaoGestao();
            }
        }
    }

    public function garantirRoleNaoProtegida(string $role): void
    {
        if (in_array($role, $this->protectedRoles(), true)) {
            throw AccessException::roleProtegida($role);
        }
    }

    public function garantirHierarquiaSobreUsuario(AdminUser $ator, AdminUser $alvo): void
    {
        if (! $this->hierarchy->podeGerir($ator, $alvo)) {
            throw AccessException::foraDaHierarquia();
        }
    }

    public function garantirHierarquiaSobreRole(AdminUser $ator, Role $role): void
    {
        if (! $this->hierarchy->podeGerirRole($ator, $role)) {
            throw AccessException::foraDaHierarquia();
        }
    }

    public function garantirDenyNaoEmSuperAdmin(AdminUser $alvo): void
    {
        $alvo->loadMissing('roles');

        if ($alvo->roles->contains('name', $this->superAdminRole())) {
            throw AccessException::denyEmSuperAdmin();
        }
    }

    private function contarSuperAdminsAtivos(): int
    {
        return AdminUser::query()
            ->where('ativo', true)
            ->whereHas('roles', fn (Builder $q): Builder => $q
                ->where('name', $this->superAdminRole())
                ->where('guard_name', 'admin'))
            ->count();
    }

    private function superAdminRole(): string
    {
        return (string) config('access.super_admin_role', 'super-admin');
    }

    /**
     * @return list<string>
     */
    private function protectedRoles(): array
    {
        /** @var list<string> $roles */
        $roles = config('access.protected_roles', []);

        return $roles;
    }

    /**
     * @return list<string>
     */
    private function selfManagementPermissions(): array
    {
        /** @var list<string> $perms */
        $perms = config('access.self_management_permissions', []);

        return $perms;
    }
}
