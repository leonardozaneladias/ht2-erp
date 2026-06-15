<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Actions\Admin\Security\ConfirmTwoFactorAction;
use App\Actions\Admin\Security\DisableTwoFactorAction;
use App\Actions\Admin\Security\EnableTwoFactorAction;
use App\Actions\Admin\Security\RegenerateRecoveryCodesAction;
use App\Livewire\Concerns\ConfirmsPassword;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\AdminUser;
use App\Services\Admin\Security\TwoFactorService;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Segurança da conta: gestão do 2FA do próprio usuário. Ações sensíveis
 * (ativar, desativar, regenerar) exigem reconfirmação de senha (ConfirmsPassword)
 * — por isso resolvem as Actions via app(), já que são chamadas pelo trait.
 */
class SegurancaConta extends Component
{
    use ConfirmsPassword;
    use EmiteNotificacoes;

    public bool $configurando = false;

    public string $svgQr = '';

    public string $codigoConfirmacao = '';

    /** @var list<string> */
    public array $recoveryCodes = [];

    public string $senhaDesconectar = '';

    public function ativar(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->ensurePasswordIsConfirmed();

        $usuario = $this->usuario();
        $secret = app(EnableTwoFactorAction::class)->execute($usuario);

        $this->svgQr = app(TwoFactorService::class)->qrCodeSvg($usuario, $secret);
        $this->configurando = true;
        $this->recoveryCodes = [];
        $this->reset('codigoConfirmacao');
    }

    public function confirmar(ConfirmTwoFactorAction $confirm): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->validate(['codigoConfirmacao' => ['required', 'string']]);

        $codigos = $confirm->execute($this->usuario(), trim($this->codigoConfirmacao));

        if ($codigos === null) {
            $this->addError('codigoConfirmacao', 'Código inválido. Verifique o app autenticador e tente novamente.');

            return;
        }

        $this->recoveryCodes = $codigos;
        $this->configurando = false;
        $this->reset('codigoConfirmacao', 'svgQr');

        $this->notificarSucesso('Verificação em duas etapas ativada.');
    }

    public function regenerar(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->ensurePasswordIsConfirmed();

        $this->recoveryCodes = app(RegenerateRecoveryCodesAction::class)->execute($this->usuario());

        $this->notificarSucesso('Novos códigos de recuperação gerados.');
    }

    public function desativar(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->ensurePasswordIsConfirmed();

        app(DisableTwoFactorAction::class)->execute($this->usuario());

        $this->reset('recoveryCodes', 'svgQr', 'configurando', 'codigoConfirmacao');

        $this->notificarSucesso('Verificação em duas etapas desativada.');
    }

    /**
     * Encerra as sessões do usuário em outros dispositivos (AuthenticateSession
     * invalida as demais sessões; a atual permanece).
     */
    public function desconectarOutrosDispositivos(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();

        $this->validate(
            ['senhaDesconectar' => ['required', 'current_password:admin']],
            ['senhaDesconectar.current_password' => 'A senha informada está incorreta.'],
        );

        $guard = Auth::guard('admin');
        assert($guard instanceof \Illuminate\Auth\SessionGuard);
        $guard->logoutOtherDevices($this->senhaDesconectar);

        app(\App\Services\Admin\AuditoriaSeguranca::class)->outrosDispositivosDesconectados($this->usuario());

        $this->reset('senhaDesconectar');
        $this->notificarSucesso('Sessões em outros dispositivos foram encerradas.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.seguranca-conta', [
            'ativo' => $this->usuario()->hasTwoFactorEnabled(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
