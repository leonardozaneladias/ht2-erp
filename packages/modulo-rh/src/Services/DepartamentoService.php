<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Services;

use HT2ERP\Rh\Models\Departamento;

/**
 * Consultas reutilizáveis de Departamento (API-ready: não recebe Request,
 * não devolve view/redirect/json; §5.6). Cresça conforme a regra de negócio.
 */
final class DepartamentoService
{
    public function encontrar(int $id): Departamento
    {
        return Departamento::query()->findOrFail($id);
    }
}
