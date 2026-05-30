<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Perfis;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true])]
#[Title('Perfis e permissões')]
class IndexPerfis extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    public function render(): View
    {
        return view('livewire.admin.perfis.index-perfis', [
            'perfis' => Role::where('guard_name', 'admin')
                ->withCount(['permissions', 'users'])
                ->orderByDesc('nivel')
                ->orderBy('name')
                ->get(),
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Role::class) ?? false,
        ]);
    }
}
