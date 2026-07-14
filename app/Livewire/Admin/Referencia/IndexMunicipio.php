<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComFicha;
use App\Models\Referencia\Municipio;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Municípios (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Municípios')]
class IndexMunicipio extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Municipio::class);
    }

    #[On('municipios::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.municipios.index-municipios', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Municipio::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Municipio::class;
    }

    /**
     * @return list<string>
     */
    protected function relacoesFicha(): array
    {
        return ['estado'];
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.municipios.edit', ['municipio' => $registro->getKey()]);
    }
}
