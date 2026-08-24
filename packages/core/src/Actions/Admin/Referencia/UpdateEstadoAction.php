<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\EstadoDTO;
use HT2ML\Core\Models\Referencia\Estado;
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
