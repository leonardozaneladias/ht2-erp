<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\TipoLogradouroDTO;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
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
