<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Actions;

use HT2ERP\Rh\DTOs\DepartamentoDTO;
use HT2ERP\Rh\Models\Departamento;
use Illuminate\Support\Facades\DB;

class UpdateDepartamentoAction
{
    public function execute(Departamento $registro, DepartamentoDTO $dto): Departamento
    {
        return DB::transaction(function () use ($registro, $dto): Departamento {
            // Auditoria automática (updated com diff) via trait Auditavel.
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
