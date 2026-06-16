<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\EstadoDTO;
use App\Models\Referencia\Estado;
use Illuminate\Support\Facades\DB;

class UpdateEstadoAction
{
    public function execute(Estado $registro, EstadoDTO $dto): Estado
    {
        return DB::transaction(function () use ($registro, $dto): Estado {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
