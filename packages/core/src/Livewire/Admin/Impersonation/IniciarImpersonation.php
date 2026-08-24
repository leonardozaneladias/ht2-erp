<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Impersonation;

use HT2ML\Core\Actions\Admin\Impersonation\IniciarImpersonationAction;
use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Livewire\Concerns\ConfirmsPassword;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de entrada na personificação, acionado pela ação "Entrar como" da tabela
 * de Usuários. Coleta o motivo e reconfirma a senha (ConfirmsPassword) antes de
 * delegar à IniciarImpersonationAction.
 */
class IniciarImpersonation extends Component
{
    use ConfirmsPassword;

    public bool $aberto = false;

    public ?int $alvoId = null;

    public string $alvoNome = '';

    public ?string $alvoAvatarUrl = null;

    public string $motivo = '';

    #[On('impersonation::abrir')]
    public function abrir(int $id): void
    {
        $alvo = AdminUser::findOrFail($id);
        $this->authorize('impersonate', $alvo);

        $this->alvoId = $alvo->id;
        $this->alvoNome = (string) $alvo->getAttribute('nome');
        $this->alvoAvatarUrl = $alvo->urlAvatar();
        $this->motivo = '';
        $this->resetErrorBag();
        $this->aberto = true;
    }

    public function confirmarEntrada(): void
    {
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);
        $this->iniciarConfirmacaoDeSenha('iniciar');
    }

    public function iniciar(): void
    {
        $this->ensurePasswordIsConfirmed();
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);

        $alvo = AdminUser::findOrFail($this->alvoId);
        $this->authorize('impersonate', $alvo);

        try {
            app(IniciarImpersonationAction::class)->execute($this->ator(), $alvo, $this->motivo);
        } catch (AccessException $e) {
            $this->addError('motivo', $e->getMessage());

            return;
        }

        $this->redirect(route('admin.dashboard'));
    }

    public function fechar(): void
    {
        $this->aberto = false;
        $this->reset('alvoId', 'alvoNome', 'alvoAvatarUrl', 'motivo');
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.impersonation.iniciar-impersonation');
    }

    private function ator(): AdminUser
    {
        $ator = Auth::guard('admin')->user();

        assert($ator instanceof AdminUser);

        return $ator;
    }
}
