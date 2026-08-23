<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Reconfirmação de senha para ações sensíveis em componentes Livewire.
 *
 * Use `iniciarConfirmacaoDeSenha('metodo', [$args])` no wire:click: se a senha
 * foi confirmada dentro da janela (config auth.password_timeout), o método é
 * executado direto; caso contrário, abre o modal de confirmação e só executa
 * após validar a senha. Inclua <x-admin.confirms-password /> na view.
 *
 * Os métodos protegidos devem resolver dependências via app() (não por injeção
 * de parâmetro), pois são chamados dinamicamente por este trait.
 */
trait ConfirmsPassword
{
    public bool $confirmandoSenha = false;

    public string $senhaConfirmacao = '';

    public string $acaoConfirmavel = '';

    /** @var array<int, mixed> */
    public array $argsConfirmaveis = [];

    /**
     * @param  array<int, mixed>  $args
     */
    public function iniciarConfirmacaoDeSenha(string $acao, array $args = []): void
    {
        if ($this->senhaConfirmadaRecentemente()) {
            $this->{$acao}(...$args);

            return;
        }

        $this->acaoConfirmavel = $acao;
        $this->argsConfirmaveis = $args;
        $this->senhaConfirmacao = '';
        $this->resetErrorBag('senhaConfirmacao');
        $this->confirmandoSenha = true;
    }

    public function confirmarSenha(): void
    {
        $usuario = Auth::guard('admin')->user();

        if (! $usuario instanceof AdminUser || ! Hash::check($this->senhaConfirmacao, $usuario->password)) {
            $this->addError('senhaConfirmacao', 'A senha informada está incorreta.');

            return;
        }

        session(['auth.password_confirmed_at' => time()]);

        $acao = $this->acaoConfirmavel;
        $args = $this->argsConfirmaveis;

        $this->cancelarConfirmacao();

        if ($acao !== '' && method_exists($this, $acao)) {
            $this->{$acao}(...$args);
        }
    }

    public function cancelarConfirmacao(): void
    {
        $this->confirmandoSenha = false;
        $this->senhaConfirmacao = '';
        $this->acaoConfirmavel = '';
        $this->argsConfirmaveis = [];
        $this->resetErrorBag('senhaConfirmacao');
    }

    protected function senhaConfirmadaRecentemente(): bool
    {
        $confirmadoEm = session('auth.password_confirmed_at');

        if (! is_int($confirmadoEm)) {
            return false;
        }

        return (time() - $confirmadoEm) < (int) config('auth.password_timeout', 10800);
    }

    /**
     * Garante, dentro da própria ação sensível, que a senha foi confirmada —
     * impede que o método público seja chamado diretamente sem confirmação.
     */
    protected function ensurePasswordIsConfirmed(): void
    {
        if (! $this->senhaConfirmadaRecentemente()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Confirmação de senha necessária.');
        }
    }
}
