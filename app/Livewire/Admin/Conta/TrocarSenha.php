<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Models\AdminUser;
use App\Support\Settings\PasswordPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Aba "Segurança" — troca da própria senha. A senha atual é a trava (sem modal
 * de reconfirmação). Aplica a política de senha configurada (PasswordPolicy).
 */
class TrocarSenha extends Component
{
    public string $senhaAtual = '';

    public string $novaSenha = '';

    public string $novaSenha_confirmation = '';

    public function trocar(): void
    {
        $this->validate([
            'senhaAtual' => ['required', 'current_password:admin'],
            'novaSenha' => ['required', 'confirmed', 'different:senhaAtual', PasswordPolicy::rule()],
        ], [
            'senhaAtual.current_password' => 'A senha atual está incorreta.',
            'novaSenha.different' => 'A nova senha deve ser diferente da atual.',
        ]);

        $usuario = $this->usuario();
        $usuario->update(['password' => $this->novaSenha]);

        session()->regenerate();

        activity('conta')
            ->causedBy($usuario)
            ->event('senha_alterada')
            ->log('Senha alterada pelo próprio usuário');

        $this->reset('senhaAtual', 'novaSenha', 'novaSenha_confirmation');
        $this->dispatch('toast', variant: 'success', message: 'Senha alterada.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.trocar-senha', [
            'politica' => PasswordPolicy::descricao(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
