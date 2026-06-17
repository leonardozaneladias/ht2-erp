<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Actions;

use HT2ERP\Rh\DTOs\DepartamentoDTO;
use HT2ERP\Rh\Models\Departamento;
use Illuminate\Support\Facades\DB;

class CreateDepartamentoAction
{
    public function execute(DepartamentoDTO $dto): Departamento
    {
        return DB::transaction(function () use ($dto): Departamento {
            // Auditoria automática (created com diff) via trait Auditavel.
            return Departamento::create($dto->paraModel());
        });
    }
}
