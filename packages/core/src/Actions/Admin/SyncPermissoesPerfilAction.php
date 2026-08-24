<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\DTOs\Admin\SyncPermissoesPerfilDTO;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AccessResolver;
use HT2ML\Core\Support\Access\AccessGuard;
use HT2ML\Core\Support\Access\PermissionRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissoesPerfilAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
        private readonly PermissionRegistry $registry,
    ) {}

    public function execute(SyncPermissoesPerfilDTO $dto, ?AdminUser $ator = null): Role
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): Role {
            /** @var Role $role */
            $role = Role::query()->where('guard_name', 'admin')->findOrFail($dto->roleId);

            $this->guard->garantirRoleNaoProtegida($role->name);

            if ($ator instanceof AdminUser) {
                $this->guard->garantirHierarquiaSobreRole($ator, $role);
            }

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
                ->withProperties(['permissions' => $permissoes])
                ->event('permissoes_atualizadas')
                ->log('Permissões do perfil atualizadas');

            return $role;
        });
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
