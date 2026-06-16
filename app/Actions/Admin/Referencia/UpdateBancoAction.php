<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\BancoDTO;
use App\Models\Referencia\Banco;
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
