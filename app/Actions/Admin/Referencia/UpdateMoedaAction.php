<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\MoedaDTO;
use App\Models\Referencia\Moeda;
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
