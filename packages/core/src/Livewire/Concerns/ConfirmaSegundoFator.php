<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Concerns;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;

/**
 * Step-up de segurança para ações sensíveis: exige um código 2FA atual (TOTP ou
 * de recuperação) antes de concluir a ação. Quando o usuário não tem 2FA, faz
 * fallback para a reconfirmação de senha (ConfirmsPassword, incluída aqui).
 * Espelha o desenho de ConfirmsPassword.
 *
 * Uso: `wire:click="iniciarConfirmacao2fa('metodo', [$args])"`; inclua na view
 * os partials `admin.partials.confirms-two-factor` e o de senha (para o
 * fallback). Os métodos protegidos devem resolver dependências via app() — são
 * chamados dinamicamente por este trait.
 */
trait ConfirmaSegundoFator
{
    use ConfirmsPassword;

    public bool $confirmando2fa = false;

    public string $codigo2fa = '';

    public bool $usarRecovery2fa = false;

    public string $acao2fa = '';

    /** @var array<int, mixed> */
    public array $args2fa = [];

    /**
     * @param  array<int, mixed>  $args
     */
    public function iniciarConfirmacao2fa(string $acao, array $args = []): void
    {
        // Sem 2FA configurado não há segundo fator a pedir: cai na senha.
        if (! $this->usuario2fa()->hasTwoFactorEnabled()) {
            $this->iniciarConfirmacaoDeSenha($acao, $args);

            return;
        }

        if ($this->segundoFatorConfirmadoRecentemente()) {
            $this->{$acao}(...$args);

            return;
        }

        $this->acao2fa = $acao;
        $this->args2fa = $args;
        $this->codigo2fa = '';
        $this->usarRecovery2fa = false;
        $this->resetErrorBag('codigo2fa');
        $this->confirmando2fa = true;
    }

    public function confirmar2fa(TwoFactorService $service): void
    {
        if (! $this->codigo2faConfere($service, $this->usuario2fa())) {
            return;
        }

        session(['auth.two_factor_confirmed_at' => time()]);

        $acao = $this->acao2fa;
        $args = $this->args2fa;

        $this->cancelarConfirmacao2fa();

        if ($acao !== '' && method_exists($this, $acao)) {
            $this->{$acao}(...$args);
        }
    }

    public function cancelarConfirmacao2fa(): void
    {
        $this->confirmando2fa = false;
        $this->codigo2fa = '';
        $this->usarRecovery2fa = false;
        $this->acao2fa = '';
        $this->args2fa = [];
        $this->resetErrorBag('codigo2fa');
    }

    protected function segundoFatorConfirmadoRecentemente(): bool
    {
        $confirmadoEm = session('auth.two_factor_confirmed_at');

        if (! is_int($confirmadoEm)) {
            return false;
        }

        return (time() - $confirmadoEm) < (int) config('auth.two_factor_confirm_timeout', 300);
    }

    /**
     * Garante, dentro da própria ação sensível, que o segundo fator foi
     * confirmado quando o usuário tem 2FA — senão exige a senha (fallback).
     */
    protected function ensureSegundoFatorConfirmado(): void
    {
        if (! $this->usuario2fa()->hasTwoFactorEnabled()) {
            $this->ensurePasswordIsConfirmed();

            return;
        }

        if (! $this->segundoFatorConfirmadoRecentemente()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Confirmação em duas etapas necessária.');
        }
    }

    private function codigo2faConfere(TwoFactorService $service, AdminUser $usuario): bool
    {
        if ($this->usarRecovery2fa) {
            $restantes = $service->consumirRecoveryCode(
                $usuario->two_factor_recovery_codes ?? [],
                trim($this->codigo2fa),
            );

            if ($restantes === null) {
                $this->addError('codigo2fa', 'Código de recuperação inválido.');

                return false;
            }

            $usuario->forceFill(['two_factor_recovery_codes' => $restantes])->save();

            return true;
        }

        if ($usuario->two_factor_secret === null) {
            $this->addError('codigo2fa', 'Código inválido ou expirado.');

            return false;
        }

        $timestamp = $service->verificarCodigo(
            $usuario->two_factor_secret,
            trim($this->codigo2fa),
            $usuario->two_factor_last_timestamp,
        );

        if ($timestamp === false) {
            $this->addError('codigo2fa', 'Código inválido ou expirado.');

            return false;
        }

        // Step-up também consome a janela TOTP: impede replay do mesmo código.
        $usuario->forceFill(['two_factor_last_timestamp' => $timestamp])->save();

        return true;
    }

    private function usuario2fa(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
