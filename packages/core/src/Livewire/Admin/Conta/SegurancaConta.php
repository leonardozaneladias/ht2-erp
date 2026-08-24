<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Conta;

use HT2ML\Core\Actions\Admin\Security\ConfirmEmailTwoFactorAction;
use HT2ML\Core\Actions\Admin\Security\ConfirmTwoFactorAction;
use HT2ML\Core\Actions\Admin\Security\DisableEmailTwoFactorAction;
use HT2ML\Core\Actions\Admin\Security\DisableTwoFactorAction;
use HT2ML\Core\Actions\Admin\Security\EnableTwoFactorAction;
use HT2ML\Core\Actions\Admin\Security\RegenerateRecoveryCodesAction;
use HT2ML\Core\Livewire\Concerns\ConfirmaSegundoFator;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use HT2ML\Core\Settings\SegurancaSettings;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Segurança da conta: gestão do 2FA do próprio usuário (app autenticador e
 * código por e-mail). Ações sensíveis (ativar, desativar, regenerar) exigem
 * reconfirmação de senha (ConfirmsPassword) ou step-up de 2FA — por isso
 * resolvem as Actions via app(), já que são chamadas pelo trait.
 */
class SegurancaConta extends Component
{
    use ConfirmaSegundoFator;
    use EmiteNotificacoes;

    public bool $configurando = false;

    public string $svgQr = '';

    public string $codigoConfirmacao = '';

    /** @var list<string> */
    public array $recoveryCodes = [];

    public string $senhaDesconectar = '';

    public bool $configurandoEmail = false;

    public string $codigoEmailConfirmacao = '';

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
        // Step-up: para desligar o 2FA é preciso provar que ainda o controla.
        $this->ensureSegundoFatorConfirmado();

        app(DisableTwoFactorAction::class)->execute($this->usuario());

        $this->reset('recoveryCodes', 'svgQr', 'configurando', 'codigoConfirmacao');

        $this->notificarSucesso('Verificação em duas etapas desativada.');
    }

    /**
     * Inicia a ativação do código por e-mail: dispara um código de verificação
     * (provando que o usuário recebe e-mails) e abre o passo de confirmação.
     */
    public function ativarEmailDoisFatores(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->ensurePasswordIsConfirmed();

        if (! app(SegurancaSettings::class)->permitir_2fa_email || $this->usuario()->two_factor_email_enabled) {
            return;
        }

        app(TwoFactorService::class)->dispararCodigoEmail($this->usuario());

        $this->configurandoEmail = true;
        $this->reset('codigoEmailConfirmacao');

        $this->notificarSucesso('Enviamos um código de verificação para o seu e-mail.');
    }

    public function reenviarCodigoEmailDoisFatores(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();

        if (! $this->configurandoEmail) {
            return;
        }

        if (! app(TwoFactorService::class)->dispararCodigoEmail($this->usuario())) {
            $this->addError('codigoEmailConfirmacao', 'Aguarde alguns segundos antes de pedir um novo código.');

            return;
        }

        $this->notificarSucesso('Novo código enviado para o seu e-mail.');
    }

    public function confirmarEmailDoisFatores(ConfirmEmailTwoFactorAction $confirm): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->validate(['codigoEmailConfirmacao' => ['required', 'string']]);

        if (! $confirm->execute($this->usuario(), trim($this->codigoEmailConfirmacao))) {
            $this->addError('codigoEmailConfirmacao', 'Código inválido ou expirado. Verifique seu e-mail e tente novamente.');

            return;
        }

        $this->configurandoEmail = false;
        $this->reset('codigoEmailConfirmacao');

        $this->notificarSucesso('Código por e-mail ativado como segundo fator.');
    }

    public function cancelarConfiguracaoEmail(): void
    {
        $this->configurandoEmail = false;
        $this->reset('codigoEmailConfirmacao');
    }

    public function desativarEmailDoisFatores(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        // Step-up: desligar um segundo fator exige provar o controle da conta.
        $this->ensureSegundoFatorConfirmado();

        app(DisableEmailTwoFactorAction::class)->execute($this->usuario());

        $this->configurandoEmail = false;
        $this->reset('codigoEmailConfirmacao');

        $this->notificarSucesso('Código por e-mail desativado.');
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

        app(\HT2ML\Core\Services\Admin\AuditoriaSeguranca::class)->outrosDispositivosDesconectados($this->usuario());

        $this->reset('senhaDesconectar');
        $this->notificarSucesso('Sessões em outros dispositivos foram encerradas.');
    }

    public function render(): View
    {
        $usuario = $this->usuario();

        return view('livewire.admin.conta.seguranca-conta', [
            'ativo' => $usuario->hasTwoFactorEnabled(),
            'restantes' => count($usuario->two_factor_recovery_codes ?? []),
            'emailAtivo' => $usuario->two_factor_email_enabled,
            'emailPermitido' => app(SegurancaSettings::class)->permitir_2fa_email,
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
