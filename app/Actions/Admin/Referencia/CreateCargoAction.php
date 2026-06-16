<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CargoDTO;
use App\Models\Referencia\Cargo;
use Illuminate\Support\Facades\DB;

class CreateCargoAction
{
    public function execute(CargoDTO $dto): Cargo
    {
        return DB::transaction(fn (): Cargo => Cargo::create($dto->paraModel()));
    }
}
