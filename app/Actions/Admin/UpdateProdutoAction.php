<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\ProdutoDTO;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

class UpdateProdutoAction
{
    public function execute(Produto $registro, ProdutoDTO $dto): Produto
    {
        return DB::transaction(function () use ($registro, $dto): Produto {
            // Auditoria automática (updated com diff) via trait Auditavel.
            $registro->update($dto->paraModel());

            return $registro->fresh() ?? $registro;
        });
    }
}
