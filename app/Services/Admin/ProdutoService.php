<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Produto;

/**
 * Consultas reutilizáveis de Produto (API-ready: não recebe Request,
 * não devolve view/redirect/json; §5.6). Cresça conforme a regra de negócio.
 */
final class ProdutoService
{
    public function encontrar(int $id): Produto
    {
        return Produto::query()->findOrFail($id);
    }
}
