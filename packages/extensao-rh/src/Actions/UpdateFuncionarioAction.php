<?php

declare(strict_types=1);

namespace HT2ML\Rh\Actions;

use HT2ML\Rh\DTOs\FuncionarioDTO;
use HT2ML\Rh\Models\Funcionario;
use Illuminate\Support\Facades\DB;

class UpdateFuncionarioAction
{
    public function execute(Funcionario $registro, FuncionarioDTO $dto): Funcionario
    {
        return DB::transaction(function () use ($registro, $dto): Funcionario {
            // Auditoria automática (updated com diff) via trait Auditavel.
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
