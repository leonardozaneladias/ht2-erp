<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auditoria;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela de logs de auditoria (append-only).
 *
 * Wrapper fino: valida o acesso e embute o grid PowerGrid
 * (App\Livewire\Admin\Auditoria\AuditoriaTable).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Logs de auditoria')]
class IndexAuditoria extends Component
{
    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('auditoria.visualizar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function render(): View
    {
        return view('livewire.admin.auditoria.index-auditoria');
    }
}
