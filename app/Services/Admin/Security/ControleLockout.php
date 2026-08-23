<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Lockout temporário por conta: conta as falhas de login por e-mail (cache) e, ao
 * exceder o limite, grava bloqueado_ate no usuário (durável) e dispara um alerta.
 * O contador por e-mail é independente do throttle de login (por e-mail+IP).
 */
final class ControleLockout
{
    public function __construct(private readonly AlertaSeguranca $alerta) {}

    public function estaBloqueada(AdminUser $usuario): bool
    {
        return $usuario->estaBloqueada();
    }

    public function registrarFalha(string $email): void
    {
        $chave = $this->chave($email);
        RateLimiter::hit($chave, $this->duracaoMinutos() * 60);

        if (RateLimiter::attempts($chave) < $this->maxFalhas()) {
            return;
        }

        $usuario = AdminUser::where('email', $email)->first();

        if (! $usuario instanceof AdminUser) {
            return;
        }

        $usuario->forceFill(['bloqueado_ate' => now()->addMinutes($this->duracaoMinutos())])->save();
        RateLimiter::clear($chave);
        $this->alerta->contaBloqueada($usuario);
    }

    public function liberar(AdminUser $usuario): void
    {
        if ($usuario->bloqueado_ate !== null) {
            $usuario->forceFill(['bloqueado_ate' => null])->save();
        }

        RateLimiter::clear($this->chave((string) $usuario->getAttribute('email')));
    }

    private function chave(string $email): string
    {
        return 'lockout:' . Str::lower($email);
    }

    private function maxFalhas(): int
    {
        return max(1, app(SegurancaSettings::class)->lockout_max_falhas);
    }

    private function duracaoMinutos(): int
    {
        return max(1, app(SegurancaSettings::class)->lockout_duracao_minutos);
    }
}
