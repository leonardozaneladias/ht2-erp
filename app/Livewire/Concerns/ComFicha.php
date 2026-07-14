<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Dá ao INDEX de um CRUD a ficha de visualização ("Ver") em drawer: um painel
 * read-only largo que abre sobre a listagem sem rota nova. É o caminho do ACL
 * de consulta — quem tem só `{modulo}.listar` (ability `view`) abre a ficha sem
 * nenhum botão de edição/exclusão. Ver docs/visualizacao.md.
 *
 * Composição (padrão do detalhe da auditoria — o drawer vive no Index, FORA do
 * morph do grid; a Table não muda): o kebab (_acoes.blade.php) dispara um evento
 * global namespaced (wire:click com dispatch de "exemplos::ver" + id); o Index
 * declara o bridge concreto com o atributo On("exemplos::ver") chamando
 * abrirFicha(id) e os hooks (modelClassFicha etc.); a view do Index renderiza
 * o x-admin.ficha-drawer (props registro/titulo/editar-url dos computeds) com
 * o partial _ficha.blade.php do módulo no slot. Exemplo completo: módulo
 * Exemplo (IndexExemplo + index-exemplos.blade.php).
 *
 * O bridge é concreto (4 linhas) porque o atributo On só interpola propriedades
 * públicas do próprio componente — não hooks.
 */
trait ComFicha
{
    use EmiteNotificacoes;

    /** Registro aberto no drawer de ficha (null = drawer sem conteúdo). */
    #[Locked]
    public ?int $fichaId = null;

    /** Registro da ficha — re-autoriza a cada render (não confia no id retido). */
    #[Computed]
    public function ficha(): ?Model
    {
        if ($this->fichaId === null) {
            return null;
        }

        $registro = $this->localizarRegistroFicha($this->fichaId);

        if ($registro === null || ! (auth('admin')->user()?->can('view', $registro) ?? false)) {
            return null;
        }

        return $registro;
    }

    #[Computed]
    public function fichaTitulo(): ?string
    {
        $registro = $this->ficha();

        return $registro !== null ? $this->tituloFicha($registro) : null;
    }

    #[Computed]
    public function fichaUrlEditar(): ?string
    {
        $registro = $this->ficha();

        return $registro !== null ? $this->urlEditarFicha($registro) : null;
    }

    /**
     * Autoriza, carrega e abre a ficha. Chamado pelo bridge #[On] do Index.
     * Registro inexistente (ou de outro tenant — o global scope filtra) vira
     * toast; a ability `view` cobre o ACL de consulta.
     */
    protected function abrirFicha(int $id): void
    {
        $registro = $this->localizarRegistroFicha($id);

        if ($registro === null) {
            $this->notificarErro('Registro não encontrado.');

            return;
        }

        $this->authorize('view', $registro);

        $this->fichaId = $id;
        unset($this->ficha, $this->fichaTitulo, $this->fichaUrlEditar);
        $this->dispatch('ficha-abrir');
    }

    /**
     * Classe do model exibido na ficha.
     *
     * @return class-string<Model>
     */
    abstract protected function modelClassFicha(): string;

    /**
     * Relações para eager load na ficha (evita N+1 nas FKs exibidas).
     *
     * @return list<string>
     */
    protected function relacoesFicha(): array
    {
        return [];
    }

    /** Título do drawer; default: atributo `nome` ou "Registro #id". */
    protected function tituloFicha(Model $registro): string
    {
        $nome = $registro->getAttribute('nome');

        return is_string($nome) && $nome !== '' ? $nome : 'Registro #' . $registro->getKey();
    }

    /** URL do botão Editar no rodapé do drawer (null = sem botão). */
    protected function urlEditarFicha(Model $registro): ?string
    {
        return null;
    }

    /**
     * Localiza o registro respeitando os global scopes (tenant); com SoftDeletes,
     * inclui a lixeira — a ficha ajuda a decidir uma restauração, e o id explícito
     * + policy `view` mantêm a segurança sem sincronizar o $verLixeira da Table.
     */
    private function localizarRegistroFicha(int $id): ?Model
    {
        $classe = $this->modelClassFicha();
        $query = $classe::query()->with($this->relacoesFicha());

        if (in_array(SoftDeletes::class, class_uses_recursive($classe), true)) {
            // Equivale a withTrashed() — escrito com métodos do Builder base
            // para não depender da macro do SoftDeletes num Builder genérico.
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query->find($id);
    }
}
