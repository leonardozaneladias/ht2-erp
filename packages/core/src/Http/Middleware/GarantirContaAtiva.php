<?php

declare(strict_types=1);

namespace HT2ML\Core\Http\Middleware;

use Closure;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que apenas contas ativas mantêm a sessão admin: um usuário desativado
 * durante a sessão é deslogado na requisição seguinte.
 */
final class GarantirContaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('admin')->user();

        if ($usuario instanceof AdminUser && ! $usuario->ativo) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('warning', 'Sua conta foi desativada.');
        }

        return $next($request);
    }
}
