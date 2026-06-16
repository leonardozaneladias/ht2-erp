<?php

declare(strict_types=1);

namespace App\Actions\Admin\Referencia;

use App\DTOs\Admin\Referencia\BancoDTO;
use App\Models\Referencia\Banco;
use Illuminate\Support\Facades\DB;

class CreateBancoAction
{
    public function execute(BancoDTO $dto): Banco
    {
        return DB::transaction(fn (): Banco => Banco::create($dto->paraModel()));
    }
}
