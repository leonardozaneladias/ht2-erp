<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\EstadoDTO;
use HT2ML\Core\Models\Referencia\Estado;
use Illuminate\Support\Facades\DB;

class CreateEstadoAction
{
    public function execute(EstadoDTO $dto): Estado
    {
        return DB::transaction(fn (): Estado => Estado::create($dto->paraModel()));
    }
}
