<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\MunicipioDTO;
use App\Models\Referencia\Municipio;
use Illuminate\Support\Facades\DB;

class UpdateMunicipioAction
{
    public function execute(Municipio $registro, MunicipioDTO $dto): Municipio
    {
        return DB::transaction(function () use ($registro, $dto): Municipio {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
