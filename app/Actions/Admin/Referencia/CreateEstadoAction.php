<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\EstadoDTO;
use App\Models\Referencia\Estado;
use Illuminate\Support\Facades\DB;

class CreateEstadoAction
{
    public function execute(EstadoDTO $dto): Estado
    {
        return DB::transaction(fn (): Estado => Estado::create($dto->paraModel()));
    }
}
