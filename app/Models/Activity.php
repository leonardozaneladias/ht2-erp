<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity do projeto: estende o model do spatie/laravel-activitylog para expor
 * o contexto de tenant (empresa_id/filial_id) gravado em cada registro.
 */
class Activity extends SpatieActivity
{
    /**
     * Isolamento de leitura da auditoria: sem a permissão
     * `auditoria.todas-empresas`, o usuário só enxerga a empresa ativa.
     * Único ponto de escopo — usado pelo grid, detalhe e timeline.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisiveisPara(Builder $query, AdminUser $user): Builder
    {
        if ($user->can('auditoria.todas-empresas')) {
            return $query;
        }

        $empresaId = app(TenantContext::class)->empresaAtivaId();

        return $empresaId === null
            ? $query->whereRaw('1 = 0')
            : $query->where('empresa_id', $empresaId);
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return BelongsTo<Filial, $this>
     */
    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class);
    }
}
