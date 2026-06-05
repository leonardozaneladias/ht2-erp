<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\Activity;
use App\Models\AdminUser;
use App\Support\Impersonation\ImpersonationContext;
use App\Support\Tenancy\TenantContext;

/**
 * Carimba o contexto ambiente da requisição em cada atividade no momento do
 * `creating`: empresa/filial ativas (tenant) e, durante uma personificação, quem
 * está por trás (impersonado_por). Ponto único de "contexto → activity_log".
 */
final class CarimbarContextoNaAtividade
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly ImpersonationContext $impersonation,
    ) {}

    public function __invoke(Activity $activity): void
    {
        // ??= respeita um empresa_id/filial_id já setado explicitamente pela ação.
        $activity->empresa_id ??= $this->tenant->empresaAtivaId();
        $activity->filial_id ??= $this->tenant->filialAtivaId();

        if (! $this->impersonation->ativo()) {
            return;
        }

        $originalId = $this->impersonation->originalId();

        if ($originalId === null) {
            return;
        }

        $original = AdminUser::find($originalId);

        $activity->properties = collect($activity->properties ?? [])
            ->put('impersonado_por', ['id' => $originalId, 'nome' => $original?->nome]);
    }
}
