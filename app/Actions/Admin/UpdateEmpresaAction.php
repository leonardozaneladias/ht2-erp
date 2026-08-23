<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\EmpresaDTO;
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
