<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\PaisDTO;
use HT2ML\Core\Models\Referencia\Pais;
use Illuminate\Support\Facades\DB;

class CreatePaisAction
{
    public function execute(PaisDTO $dto): Pais
    {
        return DB::transaction(fn (): Pais => Pais::create($dto->paraModel()));
    }
}
