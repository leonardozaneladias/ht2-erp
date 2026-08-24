<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\MoedaDTO;
use HT2ML\Core\Models\Referencia\Moeda;
use Illuminate\Support\Facades\DB;

class UpdateMoedaAction
{
    public function execute(Moeda $registro, MoedaDTO $dto): Moeda
    {
        return DB::transaction(function () use ($registro, $dto): Moeda {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
