<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Auth;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Segunda etapa do login: valida o segundo fator do usuário cujas credenciais já
 * foram aprovadas (sessão "2fa.pending"). Aceita código TOTP, código de
 * recuperação ou código enviado por e-mail. Só aqui o usuário é autenticado.
 */
#[Layout('components.admin.auth-layout')]
#[Title('Verificação em duas etapas')]
final class TwoFactorChallenge extends Component
{
    public string $codigo = '';

    public bool $usarRecovery = false;

    public string $recoveryCode = '';

    public bool $usarEmail = false;

    /** @var 'totp'|'recovery'|'email' Método que satisfez o desafio (auditoria/alerta). */
    private string $metodoVerificado = 'totp';

    public function mount(): void
    {
        $usuario = $this->usuarioPendente();

        if ($usuario === null) {
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        // Usuário só-e-mail (sem TOTP): já inicia no método e-mail e dispara o
        // envio do código. Reenvios (inclusive ao recarregar) respeitam o cooldown.
        if (! $usuario->hasTwoFactorEnabled() && $usuario->emailDoisFatoresDisponivel()) {
            $this->usarEmail = true;
            app(TwoFactorService::class)->dispararCodigoEmail($usuario);
        }
    }

    public function verificar(TwoFactorService $service): void
    {
        $pendente = session('2fa.pending');

        if (! is_array($pendente)) {
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        $chave = 'two-factor:' . $pendente['id'] . '|' . (request()->ip() ?? 'desconhecido');

        $limite = app(LimiteTentativas::class);

        if ($limite->excedido($chave)) {
            app(AuditoriaSeguranca::class)->loginBloqueado((string) ($pendente['id'] ?? 'desconhecido'));

            throw ValidationException::withMessages([
                'codigo' => __('auth.throttle', ['seconds' => $limite->disponivelEm($chave)]),
            ]);
        }

        $usuario = AdminUser::find($pendente['id']);

        if (! $usuario instanceof AdminUser || ! $usuario->precisaSegundoFator()) {
            session()->forget('2fa.pending');
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        // Fecha a janela TOCTOU: se a conta foi desativada/bloqueada entre a 1ª
        // etapa (Login) e o desafio 2FA, recusa antes de autenticar.
        if ($usuario->estaBloqueada() || ! $usuario->ativo) {
            session()->forget('2fa.pending');
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        if (! $this->codigoConfere($service, $usuario)) {
            $limite->registrar($chave);
            app(AuditoriaSeguranca::class)->desafio2faFalhou($usuario);

            return;
        }

        $limite->limpar($chave);

        Auth::guard('admin')->login($usuario, (bool) ($pendente['remember'] ?? false));
        app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, true, $this->metodoVerificado);

        // Uso de código de recuperação é evento de risco (perda do dispositivo):
        // avisa o titular para detectar acesso indevido.
        if ($this->metodoVerificado === 'recovery') {
            app(AlertaSeguranca::class)->codigoRecuperacaoUtilizado($usuario);
        }

        if ($usuario->hasRole((string) config('access.super_admin_role', 'super-admin'))) {
            app(AlertaSeguranca::class)->superAdminLogou($usuario);
        }

        session()->forget('2fa.pending');
        session()->regenerate();

        $this->redirect(
            session()->pull('url.intended', route('admin.dashboard')),
            navigate: true,
        );
    }

    /**
     * Alterna para o método e-mail e dispara o envio do código (a partir do TOTP).
     */
    public function usarMetodoEmail(TwoFactorService $service): void
    {
        $usuario = $this->usuarioPendente();

        if ($usuario === null) {
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        if (! $usuario->emailDoisFatoresDisponivel()) {
            return;
        }

        $this->usarEmail = true;
        $this->usarRecovery = false;
        $this->reset('codigo', 'recoveryCode');
        $this->resetErrorBag();

        if (! $service->dispararCodigoEmail($usuario)) {
            $this->addError('codigo', 'Um código já foi enviado há instantes. Verifique seu e-mail.');
        }
    }

    /**
     * Reenvia o código por e-mail, respeitando o cooldown entre envios.
     */
    public function reenviarCodigoEmail(TwoFactorService $service): void
    {
        $usuario = $this->usuarioPendente();

        if ($usuario === null) {
            $this->redirect(route('admin.login'), navigate: true);

            return;
        }

        if (! $usuario->emailDoisFatoresDisponivel()) {
            return;
        }

        if (! $service->dispararCodigoEmail($usuario)) {
            $this->addError('codigo', 'Aguarde alguns segundos antes de pedir um novo código.');

            return;
        }

        $this->reset('codigo');
        $this->resetErrorBag('codigo');
    }

    public function render(): View
    {
        $usuario = $this->usuarioPendente();

        return view('livewire.admin.auth.two-factor-challenge', [
            'temTotp' => $usuario?->hasTwoFactorEnabled() ?? false,
            'emailDisponivel' => $usuario?->emailDoisFatoresDisponivel() ?? false,
            'emailMascarado' => $usuario !== null ? $this->mascararEmail($usuario->email) : '',
        ]);
    }

    private function codigoConfere(TwoFactorService $service, AdminUser $usuario): bool
    {
        if ($this->usarEmail) {
            if (! $usuario->emailDoisFatoresDisponivel()
                || ! $service->verificarCodigoEmail($usuario, trim($this->codigo))) {
                $this->addError('codigo', 'Código inválido ou expirado.');

                return false;
            }

            $this->metodoVerificado = 'email';

            return true;
        }

        if ($this->usarRecovery) {
            $restantes = $service->consumirRecoveryCode(
                $usuario->two_factor_recovery_codes ?? [],
                trim($this->recoveryCode),
            );

            if ($restantes === null) {
                $this->addError('recoveryCode', 'Código de recuperação inválido.');

                return false;
            }

            $usuario->forceFill(['two_factor_recovery_codes' => $restantes])->save();
            $this->metodoVerificado = 'recovery';

            return true;
        }

        if ($usuario->two_factor_secret === null) {
            $this->addError('codigo', 'Código inválido ou expirado.');

            return false;
        }

        $timestamp = $service->verificarCodigo(
            $usuario->two_factor_secret,
            trim($this->codigo),
            $usuario->two_factor_last_timestamp,
        );

        if ($timestamp === false) {
            $this->addError('codigo', 'Código inválido ou expirado.');

            return false;
        }

        // Persiste a janela aceita: o mesmo código não vale uma segunda vez.
        $usuario->forceFill(['two_factor_last_timestamp' => $timestamp])->save();
        $this->metodoVerificado = 'totp';

        return true;
    }

    /**
     * Usuário da sessão "2fa.pending", revalidando bloqueio/ativação (TOCTOU).
     * Devolve null quando não há pendência válida — nesta etapa o usuário ainda
     * não está autenticado, então tudo vem da sessão.
     */
    private function usuarioPendente(): ?AdminUser
    {
        $pendente = session('2fa.pending');

        if (! is_array($pendente)) {
            return null;
        }

        $usuario = AdminUser::find($pendente['id']);

        if (! $usuario instanceof AdminUser || $usuario->estaBloqueada() || ! $usuario->ativo) {
            return null;
        }

        return $usuario;
    }

    /**
     * Mascara o e-mail para exibição (ex.: "j•••@dominio.com").
     */
    private function mascararEmail(string $email): string
    {
        $partes = explode('@', $email);

        if (count($partes) !== 2) {
            return $email;
        }

        return Str::mask($partes[0], '•', 1) . '@' . $partes[1];
    }
}
