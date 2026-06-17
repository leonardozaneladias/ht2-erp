<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Actions;

use HT2ERP\Rh\DTOs\FuncionarioDTO;
use HT2ERP\Rh\Models\Funcionario;
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
