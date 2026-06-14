<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Produtos;

use App\Actions\Admin\CreateProdutoAction;
use App\Actions\Admin\UpdateProdutoAction;
use App\DTOs\Admin\ProdutoDTO;
use App\Http\Requests\Admin\ProdutoRules;
use App\Models\Produto;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormProduto extends Component
{
    #[Locked]
    public ?int $produtoId = null;

    public string $nome = '';

    public string $sku = '';

    public int $preco = 0;

    public ?string $descricao = null;

    public string $status = 'rascunho';

    public function mount(?int $produto = null): void
    {
        if ($produto !== null) {
            $registro = Produto::findOrFail($produto);
            $this->authorize('update', $registro);

            $this->produtoId = $registro->id;
            $this->nome = (string) $registro->nome;
            $this->sku = (string) $registro->sku;
            $this->preco = (int) $registro->preco;
            $this->descricao = $registro->descricao;
            $this->status = $registro->status->value;

            return;
        }

        $this->authorize('create', Produto::class);
    }

    public function salvar(CreateProdutoAction $criar, UpdateProdutoAction $atualizar): void
    {
        $dados = $this->validate(
            ProdutoRules::regras($this->produtoId),
            attributes: $this->validationAttributes(),
        );

        $dto = ProdutoDTO::fromArray($dados);

        if ($this->produtoId === null) {
            $criar->execute($dto);
            session()->flash('toast.success', 'Produto criado(a) com sucesso.');
        } else {
            $atualizar->execute(Produto::findOrFail($this->produtoId), $dto);
            session()->flash('toast.success', 'Produto atualizado(a) com sucesso.');
        }

        $this->redirect(route('admin.produtos.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.produtos.form-produto', [
            'modo' => $this->produtoId === null ? 'criar' : 'editar',
        ])->title($this->produtoId === null ? 'Novo registro' : 'Editar registro');
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'nome' => 'nome',
            'sku' => 'sku',
            'preco' => 'preco',
            'descricao' => 'descricao',
            'status' => 'status',
        ];
    }
}
