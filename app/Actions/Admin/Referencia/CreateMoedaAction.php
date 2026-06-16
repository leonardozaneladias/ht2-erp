<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\MoedaDTO;
use App\Models\Referencia\Moeda;
use Illuminate\Support\Facades\DB;

class CreateMoedaAction
{
    public function execute(MoedaDTO $dto): Moeda
    {
        return DB::transaction(fn (): Moeda => Moeda::create($dto->paraModel()));
    }
}
