<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AccessResolver;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Core\Support\Tenancy\TenantResolver;
use Illuminate\Support\Facades\DB;

/**
 * Define a empresa ativa do usuário (contexto de tenancy). Persiste na coluna
 * `empresa_ativa_id`, atualiza o TenantContext (sessão), reseta a filial e
 * invalida o cache de acesso — pois os papéis efetivos mudam por empresa.
 */
class DefinirEmpresaAtivaAction
{
    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantContext $context,
        private readonly AccessResolver $accessResolver,
    ) {}

    public function execute(AdminUser $user, ?int $empresaId): AdminUser
    {
        if ($empresaId !== null && ! $this->resolver->podeAcessar($user, $empresaId)) {
            $empresaId = null;
        }

        return DB::transaction(function () use ($user, $empresaId): AdminUser {
            $user->update([
                'empresa_ativa_id' => $empresaId,
                'filial_ativa_id' => null,
            ]);

            $this->context->definirEmpresa($empresaId);
            $this->accessResolver->invalidar($user);

            return $user->fresh(['empresaAtiva']) ?? $user;
        });
    }
}
