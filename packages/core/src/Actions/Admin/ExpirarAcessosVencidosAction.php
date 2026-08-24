<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\PermissionGrant;
use HT2ML\Core\Services\Admin\AccessResolver;
use Illuminate\Support\Facades\DB;

class ExpirarAcessosVencidosAction
{
    public function __construct(private readonly AccessResolver $resolver) {}

    /**
     * Marca como revogadas as concessões vencidas (higiene/auditoria).
     * A segurança já é garantida em runtime pelo scope vigentes(); este job
     * apenas materializa o estado e gera trilha.
     *
     * @return int Quantidade de concessões expiradas.
     */
    public function execute(): int
    {
        return DB::transaction(function (): int {
            $vencidas = PermissionGrant::query()->vencidas()->get();

            if ($vencidas->isEmpty()) {
                return 0;
            }

            $afetadosIds = [];

            foreach ($vencidas as $grant) {
                $grant->update(['revoked_at' => now()]);
                $afetadosIds[$grant->admin_user_id] = $grant->admin_user_id;
            }

            foreach (AdminUser::query()->whereIn('id', $afetadosIds)->get() as $usuario) {
                $this->resolver->invalidar($usuario);
            }

            activity('acessos')
                ->withProperties(['total' => $vencidas->count()])
                ->event('acessos_expirados')
                ->log("{$vencidas->count()} acesso(s) direto(s) expiraram");

            return $vencidas->count();
        });
    }
}
