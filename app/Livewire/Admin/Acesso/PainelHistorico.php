<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Painel de histórico de acesso dentro do hub: trilha paginada e filtrável
 * dos eventos de governança de acesso (concessões, negações, sincronizações).
 */
class PainelHistorico extends Component
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

    public string $event = '';

    public string $de = '';

    public string $ate = '';

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

        return view('livewire.admin.acesso.painel-historico', [
            'eventos' => $query->paginate(10),
            'eventosDisponiveis' => self::EVENTOS_ACESSO,
        ]);
    }
}
