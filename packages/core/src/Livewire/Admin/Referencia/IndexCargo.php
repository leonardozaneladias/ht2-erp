<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComFicha;
use HT2ML\Core\Models\Referencia\Cargo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Cargos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Cargos')]
class IndexCargo extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Cargo::class);
    }

    #[On('cargos::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.cargos.index-cargos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Cargo::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Cargo::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.cargos.edit', ['cargo' => $registro->getKey()]);
    }
}
