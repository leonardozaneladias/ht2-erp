<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\SyncRolesEmpresaDTO;
use App\Services\Admin\AccessResolver;
use App\Support\Access\AccessGuard;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Sincroniza os papéis de um usuário no escopo de UMA empresa (tabela
 * admin_user_empresa_role), sem afetar papéis de outras empresas nem os papéis
 * globais de plataforma (super-admin), que são geridos à parte.
 */
class SyncRolesEmpresaAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
    ) {}

    public function execute(SyncRolesEmpresaDTO $dto, ?AdminUser $ator = null): AdminUser
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): AdminUser {
            $alvo = AdminUser::findOrFail($dto->adminUserId);

            // O escopo por empresa não gerencia papéis globais protegidos (super-admin).
            $roles = $this->semProtegidos($dto->roles);

            if ($ator instanceof AdminUser) {
                $this->guard->garantirHierarquiaSobreUsuario($ator, $alvo);

                foreach ($roles as $nome) {
                    $role = Role::query()->where('name', $nome)->where('guard_name', 'admin')->firstOrFail();
                    $this->guard->garantirHierarquiaSobreRole($ator, $role);
                }

                $this->guard->garantirAutoGestaoEmpresa($ator, $alvo, $roles);
            }

            $this->sincronizar($alvo, $dto->empresaId, $roles);

            activity('admin_users')
                ->performedOn($alvo)
                ->causedBy($ator)
                ->withProperties(['empresa_id' => $dto->empresaId, 'roles' => $roles])
                ->event('perfis_empresa_sincronizados')
                ->log('Perfis do usuário na empresa atualizados');

            $this->resolver->invalidar($alvo);

            return $alvo->fresh() ?? $alvo;
        });
    }

    /**
     * Aplica o diff (attach/detach) restrito à empresa, preservando os demais escopos.
     *
     * @param  list<string>  $roles
     */
    private function sincronizar(AdminUser $alvo, int $empresaId, array $roles): void
    {
        $roleIds = $roles === []
            ? []
            : Role::query()->where('guard_name', 'admin')->whereIn('name', $roles)->pluck('id')->all();

        $atuais = $alvo->papeisPorEmpresa()->wherePivot('empresa_id', $empresaId)->pluck('roles.id')->all();

        $adicionar = array_values(array_diff($roleIds, $atuais));
        $remover = array_values(array_diff($atuais, $roleIds));

        if ($adicionar !== []) {
            $alvo->papeisPorEmpresa()->attach($adicionar, ['empresa_id' => $empresaId]);
        }

        if ($remover !== []) {
            $alvo->papeisPorEmpresa()->wherePivot('empresa_id', $empresaId)->detach($remover);
        }
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    private function semProtegidos(array $roles): array
    {
        /** @var list<string> $protegidas */
        $protegidas = (array) config('access.protected_roles', []);

        return array_values(array_filter(
            $roles,
            static fn (string $role): bool => ! in_array($role, $protegidas, true),
        ));
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
