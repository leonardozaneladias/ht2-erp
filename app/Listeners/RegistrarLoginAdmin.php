<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AdminUser;
use App\Models\LoginHistory;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Registra cada login real do guard admin: atualiza last_login_at/ip e grava
 * uma linha no histórico. Logins de personificação (act-as) são ignorados —
 * o contexto já está ativo neste ponto (ver IniciarImpersonationAction).
 */
final class RegistrarLoginAdmin
{
    public function __construct(
        private readonly Request $request,
        private readonly ImpersonationContext $impersonation,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'admin' || ! $event->user instanceof AdminUser) {
            return;
        }

        if ($this->impersonation->ativo()) {
            return;
        }

        $usuario = $event->user;
        $ip = $this->request->ip();

        $usuario->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        LoginHistory::create([
            'admin_user_id' => $usuario->getKey(),
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }
}
