<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\SyncRolesDTO;
use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Support\Access\AccessGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SyncRolesUsuarioAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
    ) {}

    public function execute(SyncRolesDTO $dto, ?AdminUser $ator = null): AdminUser
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): AdminUser {
            $alvo = AdminUser::findOrFail($dto->adminUserId);

            if ($ator instanceof AdminUser) {
                $this->guard->garantirHierarquiaSobreUsuario($ator, $alvo);

                foreach ($dto->roles as $nome) {
                    $role = Role::query()->where('name', $nome)->where('guard_name', 'admin')->firstOrFail();
                    $this->guard->garantirHierarquiaSobreRole($ator, $role);
                }

                $this->guard->garantirAutoGestao($ator, $alvo, $dto->roles);
            }

            $this->guard->garantirNaoEsvaziarSuperAdmins($alvo, $dto->roles);

            $alvo->syncRoles($dto->roles);

            activity('admin_users')
                ->performedOn($alvo)
                ->causedBy($ator)
                ->withProperties(['roles' => $dto->roles])
                ->event('perfis_sincronizados')
                ->log('Perfis do usuário atualizados');

            $this->resolver->invalidar($alvo);

            return $alvo->fresh(['roles']);
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
