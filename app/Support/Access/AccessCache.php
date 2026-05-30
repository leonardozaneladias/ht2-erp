<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Enums\TipoConcessao;
use App\Models\AdminUser;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Cache do snapshot bruto de acesso por usuário (roles, permissões por role,
 * concessões e negações diretas vigentes). A aplicação do perfil ativo e da
 * precedência acontece no AccessResolver, em runtime, sobre este snapshot.
 */
final class AccessCache
{
    private const VERSAO_KEY = 'access.versao_global';

    /**
     * @return array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}
     */
    public function snapshot(AdminUser $user): array
    {
        $chave = sprintf('access.snapshot.%d.v%d', (int) $user->getKey(), $this->versaoGlobal());

        /** @var array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>} $dados */
        $dados = $this->store()->remember($chave, $this->ttl(), fn (): array => $this->montar($user));

        return $dados;
    }

    public function esquecer(AdminUser $user): void
    {
        $this->store()->forget(sprintf('access.snapshot.%d.v%d', (int) $user->getKey(), $this->versaoGlobal()));
    }

    public function esquecerTodos(): void
    {
        $this->store()->put(self::VERSAO_KEY, $this->versaoGlobal() + 1, now()->addDay());
    }

    /**
     * @return array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}
     */
    private function montar(AdminUser $user): array
    {
        $user->loadMissing('roles.permissions');

        $roles = [];
        $permsPorRole = [];

        foreach ($user->roles as $role) {
            /** @var Role $role */
            $roles[] = $role->name;
            $permsPorRole[$role->name] = $role->permissions
                ->map(static fn (Permission $permissao): string => $permissao->name)
                ->values()
                ->all();
        }

        $grants = [];
        $denies = [];

        $concessoes = $user->permissionGrants()->vigentes()->with('permission')->get();

        foreach ($concessoes as $grant) {
            $nome = $grant->permission?->name;

            if ($nome === null) {
                continue;
            }

            if ($grant->type === TipoConcessao::Deny) {
                $denies[] = $nome;
            } else {
                $grants[] = $nome;
            }
        }

        return [
            'roles' => array_values(array_unique($roles)),
            'perms_por_role' => $permsPorRole,
            'grants' => array_values(array_unique($grants)),
            'denies' => array_values(array_unique($denies)),
        ];
    }

    private function versaoGlobal(): int
    {
        $versao = $this->store()->get(self::VERSAO_KEY);

        if (! is_int($versao)) {
            $versao = 1;
            $this->store()->put(self::VERSAO_KEY, $versao, now()->addDay());
        }

        return $versao;
    }

    private function ttl(): int
    {
        return (int) config('access.cache_ttl', 300);
    }

    private function store(): Repository
    {
        $store = config('access.cache_store');

        return Cache::store(is_string($store) ? $store : null);
    }
}
