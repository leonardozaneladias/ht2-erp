<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\FilialDTO;
use App\Models\Empresa;
use App\Models\Filial;
use Illuminate\Support\Facades\DB;

class CriarFilialAction
{
    public function execute(Empresa $empresa, FilialDTO $dto): Filial
    {
        return DB::transaction(function () use ($empresa, $dto): Filial {
            // Auditoria automática via trait Auditavel (subject = a própria
            // filial; empresa_id é derivado do subject no carimbo de contexto).
            /** @var Filial $filial */
            $filial = $empresa->filiais()->create($dto->paraModel() + ['e_matriz' => false]);

            return $filial;
        });
    }
}
