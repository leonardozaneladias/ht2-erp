<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Actions\Admin\Security\ConfirmTwoFactorAction;
use App\Actions\Admin\Security\DisableTwoFactorAction;
use App\Actions\Admin\Security\EnableTwoFactorAction;
use App\Actions\Admin\Security\RegenerateRecoveryCodesAction;
use App\Models\AdminUser;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Segurança da conta: gestão do 2FA do próprio usuário (ativar, confirmar,
 * regenerar códigos de recuperação e desativar).
 *
 * Nota: a confirmação de senha para estas ações é adicionada no Batch 2.3.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Segurança da conta')]
class SegurancaConta extends Component
{
    public bool $configurando = false;

    public string $svgQr = '';

    public string $codigoConfirmacao = '';

    /** @var list<string> */
    public array $recoveryCodes = [];

    public function ativar(EnableTwoFactorAction $enable, TwoFactorService $service): void
    {
        $usuario = $this->usuario();
        $secret = $enable->execute($usuario);

        $this->svgQr = $service->qrCodeSvg($usuario, $secret);
        $this->configurando = true;
        $this->recoveryCodes = [];
        $this->reset('codigoConfirmacao');
    }

    public function confirmar(ConfirmTwoFactorAction $confirm): void
    {
        $this->validate(['codigoConfirmacao' => ['required', 'string']]);

        $codigos = $confirm->execute($this->usuario(), trim($this->codigoConfirmacao));

        if ($codigos === null) {
            $this->addError('codigoConfirmacao', 'Código inválido. Verifique o app autenticador e tente novamente.');

            return;
        }

        $this->recoveryCodes = $codigos;
        $this->configurando = false;
        $this->reset('codigoConfirmacao', 'svgQr');

        $this->dispatch('toast', variant: 'success', message: 'Verificação em duas etapas ativada.');
    }

    public function regenerar(RegenerateRecoveryCodesAction $action): void
    {
        $this->recoveryCodes = $action->execute($this->usuario());

        $this->dispatch('toast', variant: 'success', message: 'Novos códigos de recuperação gerados.');
    }

    public function desativar(DisableTwoFactorAction $action): void
    {
        $action->execute($this->usuario());

        $this->reset('recoveryCodes', 'svgQr', 'configurando', 'codigoConfirmacao');

        $this->dispatch('toast', variant: 'success', message: 'Verificação em duas etapas desativada.');
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
