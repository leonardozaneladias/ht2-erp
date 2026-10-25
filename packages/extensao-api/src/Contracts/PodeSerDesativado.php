<?php

declare(strict_types=1);

namespace HT2ML\Api\Contracts;

/**
 * Um usuário de API que pode ser desativado sem ser apagado.
 *
 * O GarantirPrincipalAtivo consulta isto a cada requisição autenticada — não
 * confia em o token ter sido revogado quando o status mudou.
 */
interface PodeSerDesativado
{
    public function estaAtivo(): bool;
}
