<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\MunicipioDTO;
use App\Models\Referencia\Municipio;
use Illuminate\Support\Facades\DB;

class CreateMunicipioAction
{
    public function execute(MunicipioDTO $dto): Municipio
    {
        return DB::transaction(fn (): Municipio => Municipio::create($dto->paraModel()));
    }
}
