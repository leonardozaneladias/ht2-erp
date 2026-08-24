<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Actions;

use HT2ML\ExemploDemo\DTOs\ExemploDTO;
use HT2ML\ExemploDemo\Models\Exemplo;
use Illuminate\Support\Facades\DB;

class CreateExemploAction
{
    public function execute(ExemploDTO $dto): Exemplo
    {
        return DB::transaction(function () use ($dto): Exemplo {
            // Auditoria automática (created com diff) via trait Auditavel.
            return Exemplo::create($dto->paraModel());
        });
    }
}
