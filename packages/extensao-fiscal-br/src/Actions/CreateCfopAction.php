<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\CfopDTO;
use HT2ML\FiscalBr\Models\Cfop;
use Illuminate\Support\Facades\DB;

class CreateCfopAction
{
    public function execute(CfopDTO $dto): Cfop
    {
        return DB::transaction(fn (): Cfop => Cfop::create($dto->paraModel()));
    }
}
