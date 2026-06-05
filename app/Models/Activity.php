<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity do projeto: estende o model do spatie/laravel-activitylog para expor
 * o contexto de tenant (empresa_id/filial_id) gravado em cada registro.
 */
class Activity extends SpatieActivity
{
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
