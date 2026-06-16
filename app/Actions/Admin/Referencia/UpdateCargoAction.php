<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CargoDTO;
use App\Models\Referencia\Cargo;
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
