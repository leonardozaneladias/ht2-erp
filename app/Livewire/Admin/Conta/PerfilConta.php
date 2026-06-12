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

    public string $telefone = '';

    public string $cargo = '';

    public string $bio = '';

    public mixed $avatar = null;

    public function mount(): void
    {
        $usuario = $this->usuario();

        $this->nome = (string) $usuario->getAttribute('nome');
        $this->telefone = (string) $usuario->getAttribute('telefone');
        $this->cargo = (string) $usuario->getAttribute('cargo');
        $this->bio = (string) $usuario->getAttribute('bio');
    }

    public function salvar(AtualizarPerfilAction $action): void
    {
        $dados = $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $action->execute($this->usuario(), \App\DTOs\Admin\PerfilContaDTO::fromArray($dados), $this->avatar);
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
