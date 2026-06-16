<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\PaisDTO;
use App\Models\Referencia\Pais;
use Illuminate\Support\Facades\DB;

class CreatePaisAction
{
    public function execute(PaisDTO $dto): Pais
    {
        return DB::transaction(fn (): Pais => Pais::create($dto->paraModel()));
    }
}
