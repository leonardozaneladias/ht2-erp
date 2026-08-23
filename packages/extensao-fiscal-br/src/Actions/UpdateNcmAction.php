<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\NcmDTO;
use HT2ML\FiscalBr\Models\Ncm;
use Illuminate\Support\Facades\DB;

class UpdateNcmAction
{
    public function execute(Ncm $registro, NcmDTO $dto): Ncm
    {
        return DB::transaction(function () use ($registro, $dto): Ncm {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
