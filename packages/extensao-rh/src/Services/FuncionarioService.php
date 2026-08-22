<?php

declare(strict_types=1);

namespace HT2ML\Rh\Services;

use HT2ML\Rh\Models\Funcionario;

/**
 * Consultas reutilizáveis de Funcionario (API-ready: não recebe Request,
 * não devolve view/redirect/json; §5.6). Cresça conforme a regra de negócio.
 */
final class FuncionarioService
{
    public function encontrar(int $id): Funcionario
    {
        return Funcionario::query()->findOrFail($id);
    }
}
