<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComFicha;
use App\Models\Referencia\Pais;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Países (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Países')]
class IndexPais extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Pais::class);
    }

    #[On('paises::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.paises.index-paises', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Pais::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Pais::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.paises.edit', ['pais' => $registro->getKey()]);
    }
}
