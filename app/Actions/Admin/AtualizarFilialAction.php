<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\FilialDTO;
use App\Exceptions\FilialException;
use App\Models\Filial;
use Illuminate\Support\Facades\DB;

class AtualizarFilialAction
{
    public function execute(Filial $filial, FilialDTO $dto): Filial
    {
        if ($filial->e_matriz && ! $dto->ativo) {
            throw FilialException::matrizProtegida();
        }

        return DB::transaction(function () use ($filial, $dto): Filial {
            // Auditoria automática via trait Auditavel (updated com diff).
            $filial->update($dto->paraModel());

            return $filial->fresh() ?? $filial;
        });
    }
}
