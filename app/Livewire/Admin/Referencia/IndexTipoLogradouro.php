<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComFicha;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Tipos de logradouro (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Tipos de logradouro')]
class IndexTipoLogradouro extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', TipoLogradouro::class);
    }

    #[On('tipos_logradouro::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.tipos_logradouro.index-tipos-logradouro', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', TipoLogradouro::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return TipoLogradouro::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.tipos_logradouro.edit', ['tipo_logradouro' => $registro->getKey()]);
    }
}
