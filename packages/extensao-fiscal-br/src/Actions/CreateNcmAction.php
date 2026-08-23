<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Actions;

use HT2ML\FiscalBr\DTOs\NcmDTO;
use HT2ML\FiscalBr\Models\Ncm;
use Illuminate\Support\Facades\DB;

class CreateNcmAction
{
    public function execute(NcmDTO $dto): Ncm
    {
        return DB::transaction(fn (): Ncm => Ncm::create($dto->paraModel()));
    }
}
