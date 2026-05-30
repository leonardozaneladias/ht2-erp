<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.admin.layout', ['withLivewire' => true])]
#[Title('Histórico de acesso')]
class HistoricoAcesso extends Component
{
    use WithPagination;

    /** Eventos relacionados a mudanças de acesso. */
    private const EVENTOS_ACESSO = [
        'acesso_concedido',
        'acesso_negado',
        'acesso_revogado',
        'acessos_expirados',
        'perfis_sincronizados',
        'perfis_atribuidos_em_massa',
        'permissoes_atualizadas',
        'permissoes_sincronizadas',
    ];

    #[Url(as: 'event', except: '')]
    public string $event = '';

    #[Url(as: 'de', except: '')]
    public string $de = '';

    #[Url(as: 'ate', except: '')]
    public string $ate = '';

    public function mount(): void
    {
        $user = Auth::guard('admin')->user();

        if ($user === null || ! $user->can('acessos.historico')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function limparFiltros(): void
    {
        $this->reset(['event', 'de', 'ate']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->whereIn('event', self::EVENTOS_ACESSO)
            ->latest();

        if ($this->event !== '') {
            $query->where('event', $this->event);
        }
        if ($this->de !== '') {
            $query->whereDate('created_at', '>=', $this->de);
        }
        if ($this->ate !== '') {
            $query->whereDate('created_at', '<=', $this->ate);
        }

        return view('livewire.admin.acesso.historico-acesso', [
            'eventos' => $query->paginate(20)->withQueryString(),
            'eventosDisponiveis' => self::EVENTOS_ACESSO,
        ]);
    }
}
