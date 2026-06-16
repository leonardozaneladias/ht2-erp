<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CnaeDTO;
use App\Models\Referencia\Cnae;
use Illuminate\Support\Facades\DB;

class UpdateCnaeAction
{
    public function execute(Cnae $registro, CnaeDTO $dto): Cnae
    {
        return DB::transaction(function () use ($registro, $dto): Cnae {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
