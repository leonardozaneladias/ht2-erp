<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica as preferências do usuário autenticado à request: define o locale.
 * O fuso horário é aplicado apenas na exibição de datas (não altera o app.timezone
 * global, que afeta a gravação). Nulo herda o padrão da instância.
 */
final class AplicarPreferenciasUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('admin');

        if ($usuario instanceof AdminUser && $usuario->locale !== null && $usuario->locale !== '') {
            App::setLocale($usuario->locale);
        }

        return $next($request);
    }
}
