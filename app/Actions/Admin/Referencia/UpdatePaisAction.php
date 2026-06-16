<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\PaisDTO;
use App\Models\Referencia\Pais;
use Illuminate\Support\Facades\DB;

class UpdatePaisAction
{
    public function execute(Pais $registro, PaisDTO $dto): Pais
    {
        return DB::transaction(function () use ($registro, $dto): Pais {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
