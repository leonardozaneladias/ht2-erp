<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\TipoLogradouroDTO;
use App\Models\Referencia\TipoLogradouro;
use Illuminate\Support\Facades\DB;

class UpdateTipoLogradouroAction
{
    public function execute(TipoLogradouro $registro, TipoLogradouroDTO $dto): TipoLogradouro
    {
        return DB::transaction(function () use ($registro, $dto): TipoLogradouro {
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
