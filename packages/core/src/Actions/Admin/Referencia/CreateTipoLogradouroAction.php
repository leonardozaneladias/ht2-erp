<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\TipoLogradouroDTO;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use Illuminate\Support\Facades\DB;

class CreateTipoLogradouroAction
{
    public function execute(TipoLogradouroDTO $dto): TipoLogradouro
    {
        return DB::transaction(fn (): TipoLogradouro => TipoLogradouro::create($dto->paraModel()));
    }
}
