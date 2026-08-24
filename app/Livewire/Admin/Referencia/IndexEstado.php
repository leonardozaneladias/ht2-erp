<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComFicha;
use HT2ML\Core\Models\Referencia\Estado;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Estados (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Estados')]
class IndexEstado extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Estado::class);
    }

    #[On('estados::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.estados.index-estados', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Estado::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Estado::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.estados.edit', ['estado' => $registro->getKey()]);
    }
}
