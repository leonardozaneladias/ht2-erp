<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Recursos de tabela para componentes Livewire de listagem:
 * busca, ordenação, densidade, seleção em massa e paginação.
 *
 * O componente consumidor deve:
 *  - expor a coleção paginada na view (com {{ $items->links() }});
 *  - implementar idsDaPaginaAtual(): array<int> para a seleção "página".
 */
trait WithDataTable
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    public string $sort = '';

    public string $dir = 'asc';

    public int $perPage = 15;

    public string $densidade = 'comfortable';

    /** @var array<int, int> */
    public array $selecionados = [];

    public bool $selecionarPagina = false;

    public function updatingBusca(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function ordenarPor(string $coluna): void
    {
        if ($this->sort === $coluna) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sort = $coluna;
        $this->dir = 'asc';
    }

    public function alternarDensidade(): void
    {
        $this->densidade = $this->densidade === 'compact' ? 'comfortable' : 'compact';
    }

    public function updatedSelecionarPagina(bool $valor): void
    {
        $this->selecionados = $valor ? $this->idsDaPaginaAtual() : [];
    }

    public function limparSelecao(): void
    {
        $this->reset(['selecionados', 'selecionarPagina']);
    }

    public function temSelecao(): bool
    {
        return $this->selecionados !== [];
    }

    /**
     * IDs da página atual (para o checkbox "selecionar página").
     * Sobrescreva no componente consumidor.
     *
     * @return array<int, int>
     */
    protected function idsDaPaginaAtual(): array
    {
        return [];
    }
}
