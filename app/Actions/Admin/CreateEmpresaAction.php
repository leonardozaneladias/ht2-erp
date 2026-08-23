<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\EmpresaDTO;
use HT2ML\Core\Models\Empresa;
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

            // Auditoria automática: o trait Auditavel grava o created da
            // empresa e o da filial Matriz com o diff completo.
            $empresa->filiais()->create([
                'nome' => 'Matriz',
                'e_matriz' => true,
                'ativo' => true,
            ]);

            return $empresa;
        });
    }
}
