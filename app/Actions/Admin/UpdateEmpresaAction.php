<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\EmpresaDTO;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateEmpresaAction
{
    public function execute(Empresa $empresa, EmpresaDTO $dto): Empresa
    {
        return DB::transaction(function () use ($empresa, $dto): Empresa {
            $empresa->update($dto->paraModel());

            activity('empresas')
                ->performedOn($empresa)
                ->causedBy(Auth::guard('admin')->user())
                ->event('atualizada')
                ->log('Empresa atualizada');

            return $empresa->fresh() ?? $empresa;
        });
    }
}
