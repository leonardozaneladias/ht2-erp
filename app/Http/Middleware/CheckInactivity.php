<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Encerra a sessão do admin após um período de inatividade configurável
 * (SegurancaSettings::sessao_timeout_minutos), atualizando o carimbo de última
 * atividade a cada requisição autenticada.
 */
final class CheckInactivity
{
    private const CHAVE = 'last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check()) {
            return $next($request);
        }

        $minutos = app(SegurancaSettings::class)->sessao_timeout_minutos;

        if ($minutos > 0) {
            $ultima = $request->session()->get(self::CHAVE);

            if (is_int($ultima) && (time() - $ultima) > $minutos * 60) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->with('warning', 'Sua sessão expirou por inatividade.');
            }

            $request->session()->put(self::CHAVE, time());
        }

        return $next($request);
    }
}
