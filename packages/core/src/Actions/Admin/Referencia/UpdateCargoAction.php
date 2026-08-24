<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\CargoDTO;
use HT2ML\Core\Models\Referencia\Cargo;
use Illuminate\Support\Facades\DB;

class UpdateCargoAction
{
    public function execute(Cargo $registro, CargoDTO $dto): Cargo
    {
        return DB::transaction(function () use ($registro, $dto): Cargo {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
