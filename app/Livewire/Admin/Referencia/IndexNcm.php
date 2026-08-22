<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComFicha;
use App\Models\Referencia\Ncm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de NCMs (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('NCMs')]
class IndexNcm extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Ncm::class);
    }

    #[On('ncms::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.referencia.ncms.index-ncms', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Ncm::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Ncm::class;
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.referencia.ncms.edit', ['ncm' => $registro->getKey()]);
    }
}
