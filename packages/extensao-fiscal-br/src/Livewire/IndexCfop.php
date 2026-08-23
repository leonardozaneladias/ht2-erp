<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Livewire;

use App\Livewire\Concerns\ComFicha;
use HT2ML\FiscalBr\Models\Cfop;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de CFOPs (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('CFOPs')]
class IndexCfop extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Cfop::class);
    }

    #[On('cfops::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('fiscal-br::cfops.index-cfops', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Cfop::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Cfop::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.cfops.edit', ['cfop' => $registro->getKey()]);
    }
}
