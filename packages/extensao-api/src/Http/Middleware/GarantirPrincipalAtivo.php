<?php

declare(strict_types=1);

namespace HT2ML\Api\Http\Middleware;

use Closure;
use HT2ML\Api\Contracts\PodeSerDesativado;
use HT2ML\Api\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Um usuário desativado para de ser atendido na requisição seguinte, não só no
 * próximo login.
 *
 * Revogar os tokens quando o status muda é necessário e não basta: o gancho
 * depende de o UPDATE passar pelo Eloquent. Linha alterada por SQL cru, por
 * outro sistema no mesmo banco, ou por um `DB::table()->update()` não dispara
 * evento nenhum — e o token continuaria valendo. Esta camada não confia em
 * como a coluna mudou, só no que ela diz agora.
 *
 * Uso: `->middleware(['auth:sanctum', 'api.principal-ativo'])`. Precisa rodar
 * depois do `auth:sanctum`, e roda: `Authenticate` está na lista de prioridade
 * do framework.
 */
final class GarantirPrincipalAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof PodeSerDesativado && ! $usuario->estaAtivo()) {
            return ApiResponse::erro(__('api::mensagens.conta_inativa'), Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
