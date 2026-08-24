<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\MunicipioDTO;
use HT2ML\Core\Models\Referencia\Municipio;
use Illuminate\Support\Facades\DB;

class CreateMunicipioAction
{
    public function execute(MunicipioDTO $dto): Municipio
    {
        return DB::transaction(fn (): Municipio => Municipio::create($dto->paraModel()));
    }
}
