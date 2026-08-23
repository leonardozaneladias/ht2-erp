<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\CnaeDTO;
use HT2ML\FiscalBr\Models\Cnae;
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
