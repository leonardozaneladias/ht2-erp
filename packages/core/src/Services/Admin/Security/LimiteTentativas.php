<?php

declare(strict_types=1);

namespace HT2ML\Core\Services\Admin\Security;

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate-limit de ações de autenticação com limites lidos de SegurancaSettings
 * (login_max_tentativas / login_janela_minutos), com piso de 1. Reusado por
 * Login, TwoFactorChallenge e ForgotPassword.
 */
final class LimiteTentativas
{
    public function excedido(string $chave): bool
    {
        return RateLimiter::tooManyAttempts($chave, $this->maximo());
    }

    public function registrar(string $chave): void
    {
        RateLimiter::hit($chave, $this->janelaSegundos());
    }

    public function limpar(string $chave): void
    {
        RateLimiter::clear($chave);
    }

    public function disponivelEm(string $chave): int
    {
        return RateLimiter::availableIn($chave);
    }

    private function maximo(): int
    {
        return max(1, app(SegurancaSettings::class)->login_max_tentativas);
    }

    private function janelaSegundos(): int
    {
        return max(1, app(SegurancaSettings::class)->login_janela_minutos) * 60;
    }
}
