<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\CnaeDTO;
use HT2ML\FiscalBr\Models\Cnae;
use Illuminate\Support\Facades\DB;

class CreateCnaeAction
{
    public function execute(CnaeDTO $dto): Cnae
    {
        return DB::transaction(fn (): Cnae => Cnae::create($dto->paraModel()));
    }
}
