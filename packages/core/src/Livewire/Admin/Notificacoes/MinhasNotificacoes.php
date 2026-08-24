<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Notificacoes;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Central de notificações do usuário logado: lista paginada de todas as suas
 * notificações (lidas e não lidas), com filtro, marcar como lida e excluir.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Notificações')]
final class MinhasNotificacoes extends Component
{
    use WithPagination;

    /**
     * Filtro ativo: 'todas' ou 'nao_lidas'.
     */
    public string $filtro = 'todas';

    public function definirFiltro(string $filtro): void
    {
        $this->filtro = $filtro === 'nao_lidas' ? 'nao_lidas' : 'todas';
        $this->resetPage();
    }

    public function marcarComoLida(string $id): void
    {
        Auth::guard('admin')->user()?->notifications()->whereKey($id)->first()?->markAsRead();
    }

    public function marcarTodasComoLidas(): void
    {
        Auth::guard('admin')->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function excluir(string $id): void
    {
        Auth::guard('admin')->user()?->notifications()->whereKey($id)->delete();
    }

    public function render(): View
    {
        $usuario = Auth::guard('admin')->user();

        if ($usuario === null) {
            return view('livewire.admin.notificacoes.minhas-notificacoes', [
                'notificacoes' => null,
                'naoLidas' => 0,
            ]);
        }

        $consulta = $usuario->notifications()->latest();

        if ($this->filtro === 'nao_lidas') {
            $consulta->whereNull('read_at');
        }

        return view('livewire.admin.notificacoes.minhas-notificacoes', [
            'notificacoes' => $consulta->paginate(15),
            'naoLidas' => $usuario->unreadNotifications()->count(),
        ]);
    }
}
