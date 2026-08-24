<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\DTOs\Admin\EmpresaDTO;
use HT2ML\Core\Models\Empresa;
use Illuminate\Support\Facades\DB;

class UpdateEmpresaAction
{
    public function execute(Empresa $empresa, EmpresaDTO $dto): Empresa
    {
        return DB::transaction(function () use ($empresa, $dto): Empresa {
            // Auditoria automática via trait Auditavel (updated com diff).
            $empresa->update($dto->paraModel());

            return $empresa->fresh() ?? $empresa;
        });
    }
}
