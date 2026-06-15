<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Enums\TipoConcessao;
use App\Models\AdminUser;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Cache do snapshot bruto de acesso por (usuário, empresa ativa): roles, permissões
 * por role, concessões e negações diretas vigentes. As roles consideradas são as
 * globais (spatie, valem em todas as empresas) unidas às atribuídas na empresa ativa.
 * A aplicação do perfil ativo e da precedência acontece no AccessResolver, em runtime.
 */
final class AccessCache
{
    private const VERSAO_KEY = 'access.versao_global';

    /**
     * @return array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}
     */
    public function snapshot(AdminUser $user): array
    {
        $empresaId = app(TenantContext::class)->empresaAtivaId();
        $chave = $this->chave($user, $empresaId);

        /** @var array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>} $dados */
        $dados = $this->store()->remember($chave, $this->ttl(), fn (): array => $this->montar($user, $empresaId));

        return $dados;
    }

    /**
     * Snapshot bruto de acesso para uma empresa arbitrária (não necessariamente
     * a ativa). Usado para decidir elegibilidade em listagens multi-empresa, onde
     * conta o conjunto pleno de papéis do usuário naquele tenant (sem a lente do
     * perfil ativo, que só vale para a empresa ativa).
     *
     * @return array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}
     */
    public function snapshotEmpresa(AdminUser $user, int $empresaId): array
    {
        $chave = $this->chave($user, $empresaId);

        /** @var array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>} $dados */
        $dados = $this->store()->remember($chave, $this->ttl(), fn (): array => $this->montar($user, $empresaId));

        return $dados;
    }

    /**
     * Invalida o snapshot do usuário em todas as empresas (bump da versão por usuário).
     */
    public function esquecer(AdminUser $user): void
    {
        $chave = $this->chaveVersaoUsuario($user);
        $this->store()->put($chave, $this->versaoUsuario($user) + 1, now()->addDay());
    }

    public function esquecerTodos(): void
    {
        $this->store()->put(self::VERSAO_KEY, $this->versaoGlobal() + 1, now()->addDay());
    }

    private function chave(AdminUser $user, ?int $empresaId): string
    {
        return sprintf(
            'access.snapshot.%d.e%d.vu%d.vg%d',
            (int) $user->getKey(),
            $empresaId ?? 0,
            $this->versaoUsuario($user),
            $this->versaoGlobal(),
        );
    }

    /**
     * @return array{roles: list<string>, perms_por_role: array<string, list<string>>, grants: list<string>, denies: list<string>}
     */
    private function montar(AdminUser $user, ?int $empresaId): array
    {
        $user->loadMissing('roles.permissions');

        $rolesEfetivas = collect($user->roles->all());

        if ($empresaId !== null) {
            $rolesEfetivas = $rolesEfetivas->merge($user->rolesNaEmpresa($empresaId)->all());
        }

        $roles = [];
        $permsPorRole = [];

        foreach ($rolesEfetivas as $role) {
            /** @var Role $role */
            $role->loadMissing('permissions');
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

    private function chaveVersaoUsuario(AdminUser $user): string
    {
        return sprintf('access.versao_user.%d', (int) $user->getKey());
    }

    private function versaoUsuario(AdminUser $user): int
    {
        $versao = $this->store()->get($this->chaveVersaoUsuario($user));

        if (! is_int($versao)) {
            $versao = 1;
            $this->store()->put($this->chaveVersaoUsuario($user), $versao, now()->addDay());
        }

        return $versao;
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
