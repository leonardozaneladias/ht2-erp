<?php

declare(strict_types=1);

namespace HT2ML\Core\Policies\Referencia\Concerns;

use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use Illuminate\Database\Eloquent\Model;

/**
 * Nega escrita em linha mantida pelo `referencia:sync`.
 *
 * É a primeira das duas camadas de guarda. A policy cobre o usuário comum; o
 * super-admin bypassa policies via Gate::before, então os catálogos também
 * declaram o bloqueio nos hooks `bloqueio*` do ComLixeira, que valem para todos.
 */
trait ProtegeRegistroSincronizado
{
    /** Linha sincronizada é somente-leitura; a cadastrada nesta instalação, não. */
    protected function editavel(Model $registro): bool
    {
        return ! $registro instanceof TemOrigemDeclarada
            || ! $registro->sincronizado();
    }
}
