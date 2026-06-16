<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Settings\SegurancaSettings;
use App\Support\Impersonation\ImpersonationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quando a política exige 2FA para administradores (SegurancaSettings::
 * exigir_2fa_admin), força quem ainda não configurou a fazê-lo, redirecionando
 * para a tela de segurança da conta — sem nunca devolver 403 (evita lockout).
 */
final class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('admin')->user();

        if ($usuario instanceof AdminUser
            && ! app(ImpersonationContext::class)->ativo()
            && ! $usuario->precisaSegundoFator()
            && app(SegurancaSettings::class)->exigir_2fa_admin
            && ! $request->routeIs('admin.conta')
            && ! $request->routeIs('admin.logout')) {
            return redirect()->route('admin.conta', ['aba' => 'seguranca'])
                ->with('warning', 'A verificação em duas etapas é obrigatória. Configure-a para continuar.');
        }

        return $next($request);
    }
}
