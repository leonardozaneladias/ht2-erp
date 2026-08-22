<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComFicha;
use App\Models\Referencia\Banco;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Bancos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Bancos')]
class IndexBanco extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Banco::class);
    }

    #[On('bancos::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.bancos.index-bancos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Banco::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Banco::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.bancos.edit', ['banco' => $registro->getKey()]);
    }
}
