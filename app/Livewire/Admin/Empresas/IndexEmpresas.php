<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Empresas;

use App\Models\Empresa;
use HT2ML\Core\Livewire\Concerns\ComFicha;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de empresas (wrapper fino sobre o grid PowerGrid EmpresasTable).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Empresas')]
class IndexEmpresas extends Component
{
    use ComFicha;

    public function mount(): void
    {
        $this->authorize('viewAny', Empresa::class);
    }

    #[On('empresas::ver')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view('livewire.admin.empresas.index-empresas', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Empresa::class) ?? false,
        ]);
    }

    protected function modelClassFicha(): string
    {
        return Empresa::class;
    }

    /**
     * @return list<string>
     */
    protected function relacoesFicha(): array
    {
        return ['filiais'];
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route('admin.empresas.edit', ['empresa' => $registro->getKey()]);
    }
}
