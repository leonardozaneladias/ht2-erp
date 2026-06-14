<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\ProdutoDTO;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class CreateProdutoAction
{
    public function execute(ProdutoDTO $dto): Produto
    {
        return DB::transaction(function () use ($dto): Produto {
            // Auditoria automática (created com diff) via trait Auditavel.
            return Produto::create($dto->paraModel());
        });
    }
}
