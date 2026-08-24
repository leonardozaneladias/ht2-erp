<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\MoedaDTO;
use HT2ML\Core\Models\Referencia\Moeda;
use Illuminate\Support\Facades\DB;

class CreateMoedaAction
{
    public function execute(MoedaDTO $dto): Moeda
    {
        return DB::transaction(fn (): Moeda => Moeda::create($dto->paraModel()));
    }
}
