<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auditoria;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Logs de auditoria')]
class IndexAuditoria extends Component
{
    use WithPagination;

    #[Url(as: 'log', except: '')]
    public string $logName = '';

    #[Url(as: 'event', except: '')]
    public string $event = '';

    #[Url(as: 'de', except: '')]
    public string $de = '';

    #[Url(as: 'ate', except: '')]
    public string $ate = '';

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('auditoria.visualizar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function limparFiltros(): void
    {
        $this->reset(['logName', 'event', 'de', 'ate']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Activity::query()->with(['causer', 'subject'])->latest();

        if ($this->logName !== '') {
            $query->where('log_name', $this->logName);
        }
        if ($this->event !== '') {
            $query->where('event', $this->event);
        }
        if ($this->de !== '') {
            $query->whereDate('created_at', '>=', $this->de);
        }
        if ($this->ate !== '') {
            $query->whereDate('created_at', '<=', $this->ate);
        }

        return view('livewire.admin.auditoria.index-auditoria', [
            'eventos' => $query->paginate(20)->withQueryString(),
            'logsDisponiveis' => Activity::query()
                ->select('log_name')
                ->whereNotNull('log_name')
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name')
                ->all(),
            'eventosDisponiveis' => Activity::query()
                ->select('event')
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->all(),
        ]);
    }
}
