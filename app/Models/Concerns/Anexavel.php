<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Anexo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @phpstan-ignore trait.unused */
trait Anexavel
{
    /** @return MorphMany<Anexo, $this> */
    public function anexos(): MorphMany
    {
        return $this->morphMany(Anexo::class, 'anexavel');
    }
}
