<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Cfop;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de CFOPs (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('CFOPs')]
class IndexCfop extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Cfop::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cfops.index-cfops', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Cfop::class) ?? false,
        ]);
    }
}
