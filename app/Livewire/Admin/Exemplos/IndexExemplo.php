<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exemplos;

use App\Livewire\Concerns\ComFicha;
use App\Models\Exemplo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Exemplos (wrapper fino sobre o grid PowerGrid) + ficha "Ver"
 * em drawer (ComFicha) — este módulo é a referência viva do padrão.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Exemplos')]
class IndexExemplo extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Exemplo::class);
    }

    #[On('exemplos::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.exemplos.index-exemplos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Exemplo::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Exemplo::class;
    }

    /**
     * @return list<string>
     */
    protected function relacoesFicha(): array
    {
        return ['filial'];
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.exemplos.edit', ['exemplo' => $registro->getKey()]);
    }
}
