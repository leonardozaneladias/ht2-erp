<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Enquanto a instalação não foi concluída (GeneralSettings::instalado === false),
 * encaminha qualquer acesso ao painel para o Setup Wizard.
 *
 * Tolerante a falhas: se as settings ainda não existem (antes da migração),
 * deixa a requisição seguir para não quebrar o boot/instalação.
 */
final class EnsureSystemConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $instalado = app(GeneralSettings::class)->instalado;
        } catch (Throwable) {
            return $next($request);
        }

        if (! $instalado && ! $request->routeIs('admin.setup')) {
            return redirect()->route('admin.setup');
        }

        return $next($request);
    }
}
