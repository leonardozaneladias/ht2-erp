<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\CfopDTO;
use App\Models\Referencia\Cfop;
use Illuminate\Support\Facades\DB;

class CreateCfopAction
{
    public function execute(CfopDTO $dto): Cfop
    {
        return DB::transaction(fn (): Cfop => Cfop::create($dto->paraModel()));
    }
}
