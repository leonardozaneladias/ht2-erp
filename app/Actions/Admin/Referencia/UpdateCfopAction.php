<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CfopDTO;
use App\Models\Referencia\Cfop;
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
