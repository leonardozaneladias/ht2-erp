<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\DTOs\Admin\FilialDTO;
use HT2ML\Core\Exceptions\FilialException;
use HT2ML\Core\Models\Filial;
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
