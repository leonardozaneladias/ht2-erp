<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use App\Settings\SegurancaSettings;
use App\Support\Impersonation\ImpersonationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reverte automaticamente uma personificação que passou do teto de tempo
 * (SegurancaSettings::impersonation_timeout_minutos), devolvendo o ator ao seu
 * próprio usuário. Roda logo após a autenticação.
 */
final class EncerrarImpersonationExpirada
{
    public function __construct(
        private readonly ImpersonationContext $context,
        private readonly EncerrarImpersonationAction $encerrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->ativo()
            && $this->context->expirado(app(SegurancaSettings::class)->impersonation_timeout_minutos)) {
            $this->encerrar->execute();
            session()->flash('warning', 'Personificação expirada — você voltou à sua conta.');
        }

        return $next($request);
    }
}
