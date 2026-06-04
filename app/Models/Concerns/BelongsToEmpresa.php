<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula um model de negócio a uma empresa (tenant lógico).
 *
 * - Global scope: filtra automaticamente pela empresa ativa (TenantContext).
 *   Sem empresa ativa (ex.: jobs/CLI), não filtra — defina o contexto nesses casos.
 * - Ao criar, preenche `empresa_id` com a empresa ativa se não informado.
 *
 * Use `withoutGlobalScope('empresa')` apenas conscientemente (ex.: relatórios
 * cross-empresa autorizados).
 */
trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder): void {
            $empresaId = app(TenantContext::class)->empresaAtivaId();

            if ($empresaId !== null) {
                $builder->where($builder->getModel()->getTable() . '.empresa_id', $empresaId);
            }
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('empresa_id') === null) {
                $empresaId = app(TenantContext::class)->empresaAtivaId();

                if ($empresaId !== null) {
                    $model->setAttribute('empresa_id', $empresaId);
                }
            }
        });
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
