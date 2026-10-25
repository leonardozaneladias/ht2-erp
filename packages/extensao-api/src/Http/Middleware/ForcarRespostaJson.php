<?php

declare(strict_types=1);

namespace HT2ML\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Toda rota de API negocia JSON, mesmo quando o cliente esquece o `Accept`.
 *
 * Sem isto, `$request->expectsJson()` é falso para quem não declara `Accept`, e
 * o handler de `AuthenticationException` do produto responde com o redirect
 * para o login do painel: um app recebia **302 para HTML** onde esperava 401
 * em JSON.
 *
 * O provider o põe com `prependToGroup('api')`: `Authenticate` está na lista
 * de prioridade do framework, e um middleware apenas "appendado" rodaria
 * depois dele — tarde demais, a decisão de redirecionar já teria sido tomada.
 */
final class ForcarRespostaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
