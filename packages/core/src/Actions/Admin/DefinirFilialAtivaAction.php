<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Define a filial ativa do usuário dentro da empresa ativa. Persiste na coluna
 * `filial_ativa_id` e atualiza o TenantContext. Filial nula = "todas as filiais".
 */
class DefinirFilialAtivaAction
{
    public function __construct(private readonly TenantContext $context) {}

    public function execute(AdminUser $user, ?int $filialId): AdminUser
    {
        if ($filialId !== null && ! $user->temAcessoAFilial($filialId)) {
            $filialId = null;
        }

        return DB::transaction(function () use ($user, $filialId): AdminUser {
            $user->update(['filial_ativa_id' => $filialId]);
            $this->context->definirFilial($filialId);

            return $user->fresh(['filialAtiva']) ?? $user;
        });
    }
}
