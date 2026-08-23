<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\CfopDTO;
use HT2ML\FiscalBr\Models\Cfop;
use Illuminate\Support\Facades\DB;

class UpdateCfopAction
{
    public function execute(Cfop $registro, CfopDTO $dto): Cfop
    {
        return DB::transaction(function () use ($registro, $dto): Cfop {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
