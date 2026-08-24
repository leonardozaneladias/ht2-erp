<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Referencia;

use HT2ML\Core\DTOs\Admin\Referencia\BancoDTO;
use HT2ML\Core\Models\Referencia\Banco;
use Illuminate\Support\Facades\DB;

class CreateBancoAction
{
    public function execute(BancoDTO $dto): Banco
    {
        return DB::transaction(fn (): Banco => Banco::create($dto->paraModel()));
    }
}
