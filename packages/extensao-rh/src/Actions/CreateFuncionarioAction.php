<?php

declare(strict_types=1);

namespace HT2ML\Rh\Actions;

use HT2ML\Rh\DTOs\FuncionarioDTO;
use HT2ML\Rh\Models\Funcionario;
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
