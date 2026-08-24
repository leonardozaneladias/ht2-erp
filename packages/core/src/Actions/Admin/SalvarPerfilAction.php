<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\DTOs\Admin\PerfilDTO;
use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AccessResolver;
use HT2ML\Core\Services\Admin\HierarchyResolver;
use HT2ML\Core\Support\Access\AccessGuard;
use HT2ML\Core\Support\Access\PermissionRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SalvarPerfilAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
        private readonly HierarchyResolver $hierarchy,
        private readonly PermissionRegistry $registry,
    ) {}

    public function execute(PerfilDTO $dto, ?AdminUser $ator = null): Role
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): Role {
            // Ninguém (exceto super-admin) cria/define um perfil de nível igual ou acima do seu.
            if ($ator instanceof AdminUser
                && ! $ator->hasRole($this->superAdminRole())
                && $dto->nivel >= $this->hierarchy->nivelDe($ator)) {
                throw AccessException::foraDaHierarquia();
            }

            if ($dto->roleId !== null) {
                /** @var Role $role */
                $role = Role::query()->where('guard_name', 'admin')->findOrFail($dto->roleId);
                $this->guard->garantirRoleNaoProtegida($role->name);

                if ($ator instanceof AdminUser) {
                    $this->guard->garantirHierarquiaSobreRole($ator, $role);
                }

                $role->update(['name' => $dto->nome]);
            } else {
                /** @var Role $role */
                $role = Role::create(['name' => $dto->nome, 'guard_name' => 'admin']);
            }

            DB::table('roles')->where('id', $role->getKey())->update([
                'nivel' => $dto->nivel,
                'descricao' => $dto->descricao,
            ]);

            $permissoes = array_values(array_filter(
                $dto->permissoes,
                fn (string $permissao): bool => $this->registry->existe($permissao),
            ));
            $role->syncPermissions($permissoes);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->resolver->invalidarTodos();

            activity('roles')
                ->performedOn($role)
                ->causedBy($ator)
                ->withProperties(['nivel' => $dto->nivel, 'permissions' => $permissoes])
                ->event($dto->roleId === null ? 'created' : 'updated')
                ->log($dto->roleId === null ? 'Perfil criado' : 'Perfil atualizado');

            return $role->fresh() ?? $role;
        });
    }

    private function superAdminRole(): string
    {
        return (string) config('access.super_admin_role', 'super-admin');
    }

    private function resolverAtor(?AdminUser $ator): ?AdminUser
    {
        if ($ator instanceof AdminUser) {
            return $ator;
        }

        $atual = Auth::guard('admin')->user();

        return $atual instanceof AdminUser ? $atual : null;
    }
}
