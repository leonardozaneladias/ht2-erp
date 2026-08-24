<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\PaisDTO;
use HT2ML\Core\Models\Referencia\Pais;
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
