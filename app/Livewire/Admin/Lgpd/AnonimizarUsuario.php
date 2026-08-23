<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Lgpd;

use App\Actions\Admin\Lgpd\AnonimizarUsuarioAction;
use App\Exceptions\AccessException;
use App\Livewire\Concerns\ConfirmsPassword;
use App\Models\AdminUser;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de anonimização (LGPD), acionado pela ação "Anonimizar" da tabela de
 * Usuários. Exige confirmação digitada ("ANONIMIZAR") + reconfirmação de senha.
 */
class AnonimizarUsuario extends Component
{
    use ConfirmsPassword;
    use EmiteNotificacoes;

    public bool $aberto = false;

    public ?int $alvoId = null;

    public string $alvoNome = '';

    public string $confirmacao = '';

    #[On('lgpd::anonimizar')]
    public function abrir(int $id): void
    {
        $alvo = AdminUser::findOrFail($id);
        $this->authorize('anonimizar', $alvo);

        $this->alvoId = $alvo->id;
        $this->alvoNome = (string) $alvo->getAttribute('nome');
        $this->confirmacao = '';
        $this->resetErrorBag();
        $this->aberto = true;
    }

    public function confirmar(): void
    {
        $this->validate(['confirmacao' => ['required', 'in:ANONIMIZAR']], [
            'confirmacao.in' => 'Digite ANONIMIZAR para confirmar.',
        ]);

        $this->iniciarConfirmacaoDeSenha('anonimizar');
    }

    public function anonimizar(): void
    {
        $this->ensurePasswordIsConfirmed();
        $this->validate(['confirmacao' => ['required', 'in:ANONIMIZAR']]);

        $alvo = AdminUser::findOrFail($this->alvoId);
        $this->authorize('anonimizar', $alvo);

        try {
            app(AnonimizarUsuarioAction::class)->execute($this->ator(), $alvo);
        } catch (AccessException $e) {
            $this->addError('confirmacao', $e->getMessage());

            return;
        }

        $this->aberto = false;
        $this->reset('alvoId', 'alvoNome', 'confirmacao');
        $this->notificarSucesso('Usuário anonimizado.');
        $this->dispatch('lgpd::anonimizado');
    }

    public function fechar(): void
    {
        $this->aberto = false;
        $this->reset('alvoId', 'alvoNome', 'confirmacao');
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.lgpd.anonimizar-usuario');
    }

    private function ator(): AdminUser
    {
        $ator = Auth::guard('admin')->user();

        assert($ator instanceof AdminUser);

        return $ator;
    }
}
