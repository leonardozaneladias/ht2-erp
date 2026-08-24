<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\DTOs\Admin\FilialDTO;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
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
