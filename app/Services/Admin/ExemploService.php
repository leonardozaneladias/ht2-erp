<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Exemplo;

/**
 * Consultas reutilizáveis de Exemplo (API-ready: não recebe Request,
 * não devolve view/redirect/json; §5.6). Cresça conforme a regra de negócio.
 */
final class ExemploService
{
    public function encontrar(int $id): Exemplo
    {
        return Exemplo::query()->findOrFail($id);
    }
}
