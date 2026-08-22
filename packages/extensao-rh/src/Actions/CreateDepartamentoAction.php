<?php

declare(strict_types=1);

namespace HT2ML\Rh\Actions;

use HT2ML\Rh\DTOs\DepartamentoDTO;
use HT2ML\Rh\Models\Departamento;
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
