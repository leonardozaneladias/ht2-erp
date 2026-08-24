<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AccessResolver;
use HT2ML\Core\Support\Access\AccessGuard;
use HT2ML\Core\Support\Access\PermissionRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Concede ou revoga UMA permissão em um perfil — usada pelos toggles de
 * visibilidade da Gestão de Menus. A permissão controla o módulo inteiro
 * (menu E páginas): fonte única de verdade no ACL, com os mesmos guards
 * do hub de Controle de Acesso (role protegida + hierarquia).
 */
class AlternarPermissaoPerfilAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
        private readonly PermissionRegistry $registry,
    ) {}

    public function execute(int $roleId, string $permissao, bool $conceder, ?AdminUser $ator = null): Role
    {
        if (! $this->registry->existe($permissao)) {
            throw new InvalidArgumentException("Permissão desconhecida: {$permissao}");
        }

        /** @var Role $role */
        $role = Role::query()->where('guard_name', 'admin')->findOrFail($roleId);

        $this->guard->garantirRoleNaoProtegida($role->name);

        $ator = $this->resolverAtor($ator);

        if ($ator instanceof AdminUser) {
            $this->guard->garantirHierarquiaSobreRole($ator, $role);
        }

        DB::transaction(function () use ($role, $permissao, $conceder, $ator): void {
            if ($conceder) {
                $role->givePermissionTo($permissao);
            } else {
                $role->revokePermissionTo($permissao);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->resolver->invalidarTodos();

            activity('acessos')
                ->performedOn($role)
                ->causedBy($ator)
                ->withProperties(['permissao' => $permissao, 'origem' => 'gestao-menus'])
                ->event($conceder ? 'permissao_concedida' : 'permissao_revogada')
                ->log($conceder ? 'Permissão concedida ao perfil' : 'Permissão revogada do perfil');
        });

        return $role;
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
