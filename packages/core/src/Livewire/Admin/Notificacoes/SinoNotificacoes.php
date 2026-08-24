<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Notificacoes;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Sino de notificações da topbar: contador de não lidas + dropdown com as mais
 * recentes. Atualiza por polling leve (wire:poll) e marca como lida ao abrir um
 * item. Usa o sistema nativo de notifications do Laravel (AdminUser é Notifiable).
 */
final class SinoNotificacoes extends Component
{
    /**
     * Quantidade de itens recentes exibidos no dropdown.
     */
    private const int LIMITE_RECENTES = 8;

    /**
     * Marca a notificação como lida e, se houver URL, navega até ela.
     */
    public function abrir(string $id): mixed
    {
        $notificacao = Auth::guard('admin')->user()?->notifications()->whereKey($id)->first();

        if ($notificacao === null) {
            return null;
        }

        $notificacao->markAsRead();
        unset($this->recentes, $this->naoLidas);

        $url = $notificacao->data['url'] ?? null;

        return is_string($url) && $url !== ''
            ? $this->redirect($url, navigate: true)
            : null;
    }

    public function marcarTodasComoLidas(): void
    {
        Auth::guard('admin')->user()?->unreadNotifications()->update(['read_at' => now()]);

        unset($this->recentes, $this->naoLidas);
    }

    #[Computed]
    public function naoLidas(): int
    {
        return Auth::guard('admin')->user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * @return Collection<int, \Illuminate\Notifications\DatabaseNotification>
     */
    #[Computed]
    public function recentes(): Collection
    {
        $usuario = Auth::guard('admin')->user();

        if ($usuario === null) {
            return collect();
        }

        return $usuario->notifications()->latest()->limit(self::LIMITE_RECENTES)->get();
    }

    public function render(): View
    {
        return view('livewire.admin.notificacoes.sino-notificacoes');
    }
}
