<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\MunicipioDTO;
use HT2ML\Core\Models\Referencia\Municipio;
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
