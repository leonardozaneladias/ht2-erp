<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Support\Access\AccessGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkUserStatusAction
{
    public function __construct(
        private readonly AccessGuard $guard,
        private readonly AccessResolver $resolver,
    ) {}

    /**
     * @param  list<int>  $ids
     * @return int Quantidade de usuários afetados.
     */
    public function execute(array $ids, bool $ativo, ?AdminUser $ator = null): int
    {
        $ator = $this->resolverAtor($ator);

        return DB::transaction(function () use ($ids, $ativo, $ator): int {
            $afetados = 0;

            foreach ($ids as $id) {
                $alvo = AdminUser::find($id);

                if ($alvo === null) {
                    continue;
                }

                if ($ator instanceof AdminUser) {
                    if ($ator->is($alvo)) {
                        continue;
                    }

                    $this->guard->garantirHierarquiaSobreUsuario($ator, $alvo);
                }

                if (! $ativo) {
                    $this->guard->garantirPodeDesativar($alvo);
                }

                if ($alvo->ativo === $ativo) {
                    continue;
                }

                $alvo->update(['ativo' => $ativo]);
                $this->resolver->invalidar($alvo);
                $afetados++;
            }

            activity('admin_users')
                ->causedBy($ator)
                ->withProperties(['ativo' => $ativo, 'total' => $afetados])
                ->event($ativo ? 'reativados_em_massa' : 'desativados_em_massa')
                ->log(($ativo ? 'Reativados' : 'Desativados') . " {$afetados} usuário(s) em massa");

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
