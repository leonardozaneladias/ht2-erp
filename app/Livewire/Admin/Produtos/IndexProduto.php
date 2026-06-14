<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Produtos;

use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Listagem de Produtos (wrapper fino sobre o grid PowerGrid).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Produtos')]
class IndexProduto extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Produto::class);
    }

    public function render(): View
    {
        return view('livewire.admin.produtos.index-produtos', [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', Produto::class) ?? false,
        ]);
    }
}
