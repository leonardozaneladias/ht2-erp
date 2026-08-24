<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Actions;

use HT2ML\ExemploDemo\DTOs\ExemploDTO;
use HT2ML\ExemploDemo\Models\Exemplo;
use Illuminate\Support\Facades\DB;

class UpdateExemploAction
{
    public function execute(Exemplo $registro, ExemploDTO $dto): Exemplo
    {
        return DB::transaction(function () use ($registro, $dto): Exemplo {
            // Auditoria automática (updated com diff) via trait Auditavel.
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
