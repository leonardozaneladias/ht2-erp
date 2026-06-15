<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

/**
 * Ponto ÚNICO de disparo de notificações (toasts) no backend.
 *
 * Use os métodos semânticos em qualquer componente Livewire em vez de chamar
 * `$this->dispatch('toast', ...)` ou `session()->flash(...)` na mão:
 *
 *   $this->notificarSucesso('Registro salvo com sucesso.');
 *   $this->notificarErro($e->getMessage());
 *
 * Quando o método REDIRECIONA logo em seguida (ex.: salvar e voltar à listagem),
 * use notificarAposRedirect() — a mensagem sobrevive ao redirect via sessão e o
 * layout a converte em toast no próximo carregamento.
 *
 * A aparência e o comportamento da pílula ficam, respectivamente, em
 * resources/views/components/shared/toast-container.blade.php e
 * resources/js/admin/toast.js.
 *
 * @mixin \Livewire\Component
 */
trait EmiteNotificacoes
{
    protected function notificarSucesso(string $mensagem, ?string $titulo = null): void
    {
        $this->emitirToast('success', $mensagem, $titulo);
    }

    protected function notificarErro(string $mensagem, ?string $titulo = null): void
    {
        $this->emitirToast('danger', $mensagem, $titulo);
    }

    protected function notificarAviso(string $mensagem, ?string $titulo = null): void
    {
        $this->emitirToast('warning', $mensagem, $titulo);
    }

    protected function notificarInfo(string $mensagem, ?string $titulo = null): void
    {
        $this->emitirToast('info', $mensagem, $titulo);
    }

    /**
     * Notifica após um redirect (a mensagem fica na sessão flash até o próximo
     * carregamento). Variantes: success | danger | warning | info.
     */
    protected function notificarAposRedirect(string $variante, string $mensagem, ?string $titulo = null): void
    {
        session()->flash('notify', array_filter(
            ['variant' => $variante, 'message' => $mensagem, 'title' => $titulo],
            static fn (?string $valor): bool => $valor !== null,
        ));
    }

    private function emitirToast(string $variante, string $mensagem, ?string $titulo): void
    {
        $this->dispatch('toast', variant: $variante, message: $mensagem, title: $titulo);
    }
}
