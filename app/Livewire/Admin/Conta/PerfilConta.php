<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Actions\Admin\Conta\AtualizarPerfilAction;
use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Aba "Perfil" da Minha Conta: avatar, nome e resumo (leitura) de papéis,
 * empresas e último login. Opera sempre sobre o próprio usuário autenticado.
 */
class PerfilConta extends Component
{
    use WithFileUploads;

    public string $nome = '';

    public mixed $avatar = null;

    public function mount(): void
    {
        $this->nome = (string) $this->usuario()->getAttribute('nome');
    }

    public function salvar(AtualizarPerfilAction $action): void
    {
        $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $action->execute($this->usuario(), $this->nome, $this->avatar);
        $this->reset('avatar');

        $this->dispatch('toast', variant: 'success', message: 'Perfil atualizado.');
    }

    public function removerAvatar(AtualizarPerfilAction $action): void
    {
        $action->removerAvatar($this->usuario());

        $this->dispatch('toast', variant: 'success', message: 'Foto removida.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.perfil-conta', [
            'usuario' => $this->usuario(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
