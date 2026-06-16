<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\NcmDTO;
use App\Models\Referencia\Ncm;
use Illuminate\Support\Facades\DB;

class CreateNcmAction
{
    public function execute(NcmDTO $dto): Ncm
    {
        return DB::transaction(fn (): Ncm => Ncm::create($dto->paraModel()));
    }
}
