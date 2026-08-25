<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Grid;

use HT2ML\Core\Livewire\Concerns\ComFicha;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Base do componente de listagem: o invólucro fino em volta do grid.
 *
 * Os treze componentes `Index*` do repositório tinham 53 linhas cada e 75% do
 * conteúdo idêntico — `mount()` autorizando `viewAny`, um bridge `#[On]` de
 * quatro linhas, `render()` passando `podeCriar`, e os dois hooks do ComFicha.
 *
 * O bridge era concreto por uma limitação registrada em `ComFicha`: o atributo
 * `#[On]` só interpola PROPRIEDADE PÚBLICA do componente, não retorno de
 * método. A saída é declarar `public string $eventoVer` e usar
 * `#[On('{eventoVer}')]` — o mesmo truque que `ComLixeira` já usava com
 * `{tableName}`. A limitação era real; o que faltava era aplicar a solução que
 * já existia ao lado.
 */
abstract class RecursoIndex extends Component
{
    use ComFicha;

    /**
     * Pública porque `#[On('{eventoVer}')]` interpola propriedade, não método.
     * Preenchida no boot() a partir de recurso().
     */
    public string $eventoVer = '';

    public function boot(): void
    {
        $this->eventoVer = $this->recurso() . '::ver';
    }

    public function mount(): void
    {
        $this->authorize('viewAny', $this->model());
    }

    #[On('{eventoVer}')]
    public function verRegistro(int $id): void
    {
        $this->abrirFicha($id);
    }

    public function render(): View
    {
        return view($this->view(), [
            'podeCriar' => Auth::guard('admin')->user()?->can('create', $this->model()) ?? false,
            ...$this->dadosDaView(),
        ]);
    }

    // ------------------------------------------------------------- contrato

    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Chave plural do recurso: 'cargos'. Precisa bater com a da RecursoTable. */
    abstract protected function recurso(): string;

    /** Prefixo das rotas nomeadas: 'admin.referencia.cargos'. */
    abstract protected function rotaBase(): string;

    /** Nome da view da listagem. */
    abstract protected function view(): string;

    // ----------------------------------------------------------- derivações

    /**
     * Dados extras para a view (fuga de nível 2).
     *
     * @return array<string, mixed>
     */
    protected function dadosDaView(): array
    {
        return [];
    }

    /** @return class-string<Model> */
    protected function modelClassFicha(): string
    {
        return $this->model();
    }

    protected function urlEditarFicha(Model $registro): ?string
    {
        return route($this->rotaBase() . '.edit', [$registro->getKey()]);
    }
}
