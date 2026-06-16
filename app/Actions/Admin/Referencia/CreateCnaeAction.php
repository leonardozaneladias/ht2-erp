<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CnaeDTO;
use App\Models\Referencia\Cnae;
use Illuminate\Support\Facades\DB;

class CreateCnaeAction
{
    public function execute(CnaeDTO $dto): Cnae
    {
        return DB::transaction(fn (): Cnae => Cnae::create($dto->paraModel()));
    }
}
