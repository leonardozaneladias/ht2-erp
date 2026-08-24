<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\CargoDTO;
use HT2ML\Core\Models\Referencia\Cargo;
use Illuminate\Support\Facades\DB;

class CreateCargoAction
{
    public function execute(CargoDTO $dto): Cargo
    {
        return DB::transaction(fn (): Cargo => Cargo::create($dto->paraModel()));
    }
}
