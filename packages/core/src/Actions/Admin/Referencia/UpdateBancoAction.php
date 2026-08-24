<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\BancoDTO;
use HT2ML\Core\Models\Referencia\Banco;
use Illuminate\Support\Facades\DB;

class UpdateBancoAction
{
    public function execute(Banco $registro, BancoDTO $dto): Banco
    {
        return DB::transaction(function () use ($registro, $dto): Banco {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
