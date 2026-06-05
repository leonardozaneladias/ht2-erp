<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use App\Models\AdminUser;
use App\Services\Admin\AuditoriaSeguranca;
use App\Services\Admin\Security\AlertaSeguranca;
use App\Services\Admin\Security\LimiteTentativas;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Segunda etapa do login: valida o código TOTP (ou um código de recuperação)
 * para o usuário cujas credenciais já foram aprovadas (sessão "2fa.pending").
 * Só aqui o usuário é de fato autenticado.
 */
#[Layout('components.admin.auth-layout')]
#[Title('Verificação em duas etapas')]
final class TwoFactorChallenge extends Component
{
    public string $codigo = '';

    public bool $usarRecovery = false;

    public string $recoveryCode = '';

    public function mount(): void
    {
        if (! session()->has('2fa.pending')) {
            $this->redirect(route('admin.login'), navigate: true);
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

        if (! $usuario instanceof AdminUser || ! $usuario->hasTwoFactorEnabled()) {
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
        app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, true);

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

    public function render(): View
    {
        return view('livewire.admin.auth.two-factor-challenge');
    }

    private function codigoConfere(TwoFactorService $service, AdminUser $usuario): bool
    {
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

            return true;
        }

        if ($usuario->two_factor_secret === null
            || ! $service->verificarCodigo($usuario->two_factor_secret, trim($this->codigo))) {
            $this->addError('codigo', 'Código inválido ou expirado.');

            return false;
        }

        return true;
    }
}
