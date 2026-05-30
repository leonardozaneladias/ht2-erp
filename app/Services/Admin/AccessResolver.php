<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTOs\Admin\AcessoEfetivoDTO;
use App\Enums\OrigemAcesso;
use App\Enums\TipoConcessao;
use App\Models\AdminUser;
use App\Models\PermissionGrant;
use App\Support\Access\AccessCache;
use App\Support\Access\PermissionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

/**
 * Núcleo de resolução de acesso. Precedência:
 * super-admin (bypass) > deny direto > grant direto > permissão de role.
 * Com perfil ativo (restrição real), apenas as permissões da role ativa contam
 * no passo de role — e somente para o próprio usuário logado.
 *
 * Não chama $user->can() (evita recursão no Gate): lê do snapshot e das roles.
 */
final class AccessResolver
{
    /** @var array<int, ?string> */
    private array $perfilAtivoMemo = [];

    public function __construct(
        private readonly AccessCache $cache,
        private readonly PermissionRegistry $registry,
    ) {}

    /**
     * @return bool|null true permite, false nega, null sem opinião (Policy decide).
     */
    public function decide(AdminUser $user, string $ability): ?bool
    {
        if ($this->ehSuperAdmin($user)) {
            return true;
        }

        $snapshot = $this->cache->snapshot($user);

        if (in_array($ability, $snapshot['denies'], true)) {
            return false;
        }

        if (in_array($ability, $snapshot['grants'], true)) {
            return true;
        }

        if (in_array($ability, $this->permsDeRoles($user, $snapshot), true)) {
            return true;
        }

        return null;
    }

    /**
     * Permissões líquidas efetivas (já com perfil ativo e denies aplicados).
     *
     * @return Collection<int, string>
     */
    public function permissoesEfetivas(AdminUser $user): Collection
    {
        if ($this->ehSuperAdmin($user)) {
            return collect($this->registry->nomes());
        }

        $snapshot = $this->cache->snapshot($user);

        return collect($this->permsDeRoles($user, $snapshot))
            ->merge($snapshot['grants'])
            ->unique()
            ->reject(static fn (string $perm): bool => in_array($perm, $snapshot['denies'], true))
            ->values();
    }

    public function explicar(AdminUser $user, string $ability): AcessoEfetivoDTO
    {
        if ($this->ehSuperAdmin($user)) {
            return new AcessoEfetivoDTO($ability, true, OrigemAcesso::SuperAdmin);
        }

        $snapshot = $this->cache->snapshot($user);

        if (in_array($ability, $snapshot['denies'], true)) {
            return $this->dtoDoGrant($user, $ability, TipoConcessao::Deny, false, OrigemAcesso::Deny);
        }

        if (in_array($ability, $snapshot['grants'], true)) {
            return $this->dtoDoGrant($user, $ability, TipoConcessao::Grant, true, OrigemAcesso::GrantDireto);
        }

        $role = $this->roleQueConcede($user, $ability, $snapshot);

        if ($role !== null) {
            return new AcessoEfetivoDTO($ability, true, OrigemAcesso::Role, roleDeOrigem: $role);
        }

        return new AcessoEfetivoDTO($ability, false, OrigemAcesso::Ausente);
    }

    public function invalidar(AdminUser $user): void
    {
        $this->cache->esquecer($user);
    }

    public function invalidarTodos(): void
    {
        $this->cache->esquecerTodos();
    }

    private function dtoDoGrant(
        AdminUser $user,
        string $ability,
        TipoConcessao $tipo,
        bool $permitido,
        OrigemAcesso $origem,
    ): AcessoEfetivoDTO {
        $grant = $this->grantDecisor($user, $ability, $tipo);

        return new AcessoEfetivoDTO(
            ability: $ability,
            permitido: $permitido,
            origem: $origem,
            grantId: $grant !== null ? (int) $grant->getKey() : null,
            expiraEm: $grant?->expires_at,
            motivo: $grant?->reason,
        );
    }

    /**
     * Permissões via roles, respeitando o perfil ativo (restrição real).
     *
     * @param  array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}  $snapshot
     * @return list<string>
     */
    private function permsDeRoles(AdminUser $user, array $snapshot): array
    {
        $perms = [];

        foreach ($this->rolesConsideradas($user, $snapshot) as $role) {
            foreach ($snapshot['perms_por_role'][$role] ?? [] as $perm) {
                $perms[] = $perm;
            }
        }

        return array_values(array_unique($perms));
    }

    /**
     * @param  array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}  $snapshot
     * @return list<string>
     */
    private function rolesConsideradas(AdminUser $user, array $snapshot): array
    {
        $perfilAtivo = $this->perfilAtivoNome($user, $snapshot['roles']);

        return $perfilAtivo !== null ? [$perfilAtivo] : $snapshot['roles'];
    }

    /**
     * @param  array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}  $snapshot
     */
    private function roleQueConcede(AdminUser $user, string $ability, array $snapshot): ?string
    {
        foreach ($this->rolesConsideradas($user, $snapshot) as $role) {
            if (in_array($ability, $snapshot['perms_por_role'][$role] ?? [], true)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Nome da role ativa (lente) — só para o próprio usuário logado e se ele a possui.
     *
     * @param  list<string>  $rolesDoUsuario
     */
    private function perfilAtivoNome(AdminUser $user, array $rolesDoUsuario): ?string
    {
        $uid = (int) $user->getKey();

        if (! array_key_exists($uid, $this->perfilAtivoMemo)) {
            $this->perfilAtivoMemo[$uid] = $this->resolverPerfilAtivoNome($user);
        }

        $nome = $this->perfilAtivoMemo[$uid];

        return $nome !== null && in_array($nome, $rolesDoUsuario, true) ? $nome : null;
    }

    private function resolverPerfilAtivoNome(AdminUser $user): ?string
    {
        // A lente de perfil ativo só se aplica ao próprio usuário logado.
        // Em console/sem login, Auth retorna null e não há restrição.
        $logadoId = Auth::guard('admin')->id();

        if ($logadoId === null || (int) $user->getKey() !== (int) $logadoId) {
            return null;
        }

        $id = session('admin.perfil_ativo_role_id');

        if (! is_numeric($id)) {
            return null;
        }

        $nome = Role::query()
            ->where('id', (int) $id)
            ->where('guard_name', 'admin')
            ->value('name');

        return is_string($nome) ? $nome : null;
    }

    private function grantDecisor(AdminUser $user, string $ability, TipoConcessao $tipo): ?PermissionGrant
    {
        return $user->permissionGrants()
            ->vigentes()
            ->doTipo($tipo)
            ->whereHas('permission', static fn (Builder $q): Builder => $q->where('name', $ability))
            ->with('permission')
            ->orderByRaw('expires_at IS NULL DESC')
            ->orderBy('expires_at', 'desc')
            ->first();
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains('name', (string) config('access.super_admin_role', 'super-admin'));
    }
}
