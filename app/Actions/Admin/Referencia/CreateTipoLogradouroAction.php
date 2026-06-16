<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\TipoLogradouroDTO;
use App\Models\Referencia\TipoLogradouro;
use Illuminate\Support\Facades\DB;

class CreateTipoLogradouroAction
{
    public function execute(TipoLogradouroDTO $dto): TipoLogradouro
    {
        return DB::transaction(fn (): TipoLogradouro => TipoLogradouro::create($dto->paraModel()));
    }
}
