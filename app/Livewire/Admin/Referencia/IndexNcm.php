<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Models\Referencia\Ncm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de NCMs (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('NCMs')]
class IndexNcm extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Ncm::class);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.ncms.index-ncms', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Ncm::class) ?? false,
        ]);
    }
}
