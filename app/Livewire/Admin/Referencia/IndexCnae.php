<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Cnae;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de CNAEs (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('CNAEs')]
class IndexCnae extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Cnae::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cnaes.index-cnaes', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Cnae::class) ?? false,
        ]);
    }
}
