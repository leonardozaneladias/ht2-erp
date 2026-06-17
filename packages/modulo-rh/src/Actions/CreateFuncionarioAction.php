<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Actions;

use HT2ERP\Rh\DTOs\FuncionarioDTO;
use HT2ERP\Rh\Models\Funcionario;
use Illuminate\Support\Facades\DB;

class CreateFuncionarioAction
{
    public function execute(FuncionarioDTO $dto): Funcionario
    {
        return DB::transaction(function () use ($dto): Funcionario {
            // Auditoria automática (created com diff) via trait Auditavel.
            return Funcionario::create($dto->paraModel());
        });
    }
}
