<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\EmpresaDTO;
use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Cria uma empresa e sua filial Matriz (toda empresa nasce com a matriz).
 */
class CreateEmpresaAction
{
    public function execute(EmpresaDTO $dto): Empresa
    {
        return DB::transaction(function () use ($dto): Empresa {
            $empresa = Empresa::create($dto->paraModel());

            $empresa->filiais()->create([
                'nome' => 'Matriz',
                'e_matriz' => true,
                'ativo' => true,
            ]);

            activity('empresas')
                ->performedOn($empresa)
                ->causedBy(Auth::guard('admin')->user())
                ->event('criada')
                ->log('Empresa criada');

            return $empresa;
        });
    }
}
