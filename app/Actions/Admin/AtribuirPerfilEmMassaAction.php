<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AtribuicaoPerfilMassaDTO;
use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Support\Access\AccessGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AtribuirPerfilEmMassaAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
    ) {}

    /**
     * @return int Quantidade de usuários afetados.
     */
    public function execute(AtribuicaoPerfilMassaDTO $dto, ?AdminUser $ator = null): int
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($dto, $ator): int {
            if ($ator instanceof AdminUser) {
                foreach ($dto->roles as $nome) {
                    $role = Role::query()->where('name', $nome)->where('guard_name', 'admin')->firstOrFail();
                    $this->guard->garantirHierarquiaSobreRole($ator, $role);
                }
            }

            $afetados = 0;

            foreach ($dto->adminUserIds as $id) {
                $alvo = AdminUser::find($id);

                if ($alvo === null) {
                    continue;
                }

                if ($ator instanceof AdminUser) {
                    $this->guard->garantirHierarquiaSobreUsuario($ator, $alvo);
                }

                $dto->substituir ? $alvo->syncRoles($dto->roles) : $alvo->assignRole($dto->roles);

                $this->resolver->invalidar($alvo);
                $afetados++;
            }

            activity('admin_users')
                ->causedBy($ator)
                ->withProperties([
                    'roles' => $dto->roles,
                    'substituir' => $dto->substituir,
                    'total' => $afetados,
                ])
                ->event('perfis_atribuidos_em_massa')
                ->log("Perfis atribuídos em massa a {$afetados} usuário(s)");

            return $afetados;
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
