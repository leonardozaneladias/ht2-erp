<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Attributes\On;

/**
 * Dá a uma tabela PowerGrid de um model com SoftDeletes o fluxo de lixeira:
 * alternar entre ativos e excluídos, excluir (→ lixeira), restaurar e excluir
 * definitivamente. Espelha FiltraPorMultiEmpresa e compõe POR FORA do escopo
 * multi-empresa — os global scopes `empresa` e SoftDeletingScope são
 * independentes, então `onlyTrashed()` continua respeitando o tenant ativo.
 *
 * A tabela informa a classe do model via modelClassLixeira() e compõe o escopo
 * de lixeira no datasource (por fora do multi-empresa, quando houver):
 *
 *   public function datasource(): Builder
 *   {
 *       return $this->aplicarLixeira($this->aplicarEscopoMultiEmpresa(Exemplo::query()));
 *   }
 *
 * As três permissões são checadas pela Policy do model via $this->authorize():
 * `delete` (→ lixeira), `restore` e `forceDelete` (excluir definitivamente). As
 * ações da linha vivem em _acoes.blade.php, condicionadas a $verLixeira e
 * guardadas por @can; o toggle "Ver lixeira/ativos" entra via includeViewOnTop.
 *
 * Os eventos de confirmação são namespaced por $tableName (interpolação dinâmica
 * do #[On]) para não colidir quando há 2+ grids na mesma página.
 *
 * @mixin \PowerComponents\LivewirePowerGrid\PowerGridComponent
 */
trait ComLixeira
{
    use EmiteNotificacoes;

    /** Alterna a listagem entre registros ativos e a lixeira (só excluídos). */
    public bool $verLixeira = false;

    /** Alterna entre ativos e lixeira, voltando à 1ª página e limpando seleção. */
    public function alternarLixeira(): void
    {
        $this->verLixeira = ! $this->verLixeira;
        $this->resetPage();
        $this->checkboxValues = [];
    }

    // ---- Excluir (→ lixeira) ---------------------------------------------

    public function solicitarExcluir(int $id): void
    {
        $this->authorize('delete', $this->registroAtivo($id));

        $this->dispatch(
            'confirm',
            title: 'Mover para a lixeira?',
            text: 'O registro sai da listagem, mas pode ser restaurado da lixeira.',
            destructive: true,
            onConfirm: $this->tableName . '::excluir',
            params: ['id' => $id],
        );
    }

    #[On('{tableName}::excluir')]
    public function excluir(int $id): void
    {
        $registro = $this->registroAtivo($id);
        $this->authorize('delete', $registro);

        $bloqueio = $this->bloqueioExclusao($registro);
        if ($bloqueio !== null) {
            $this->notificarErro($bloqueio);

            return;
        }

        $registro->delete();
        $this->notificarSucesso('Registro movido para a lixeira.');
    }

    // ---- Restaurar -------------------------------------------------------

    public function solicitarRestaurar(int $id): void
    {
        $this->authorize('restore', $this->registroNaLixeira($id));

        $this->dispatch(
            'confirm',
            title: 'Restaurar registro?',
            text: 'O registro volta para a listagem de ativos.',
            destructive: false,
            onConfirm: $this->tableName . '::restaurar',
            params: ['id' => $id],
        );
    }

    #[On('{tableName}::restaurar')]
    public function restaurar(int $id): void
    {
        $registro = $this->registroNaLixeira($id);
        $this->authorize('restore', $registro);

        $bloqueio = $this->bloqueioRestauracao($registro);
        if ($bloqueio !== null) {
            $this->notificarErro($bloqueio);

            return;
        }

        $registro->restore();
        $this->notificarSucesso('Registro restaurado.');
    }

    // ---- Excluir definitivamente (force-delete) --------------------------

    public function solicitarExcluirDefinitivo(int $id): void
    {
        $registro = $this->registroNaLixeira($id);
        $this->authorize('forceDelete', $registro);

        $this->dispatch(
            'confirm',
            title: 'Excluir definitivamente?',
            text: $this->textoExcluirDefinitivo($registro),
            destructive: true,
            confirmText: 'Sim, excluir definitivamente',
            onConfirm: $this->tableName . '::excluir-definitivo',
            params: ['id' => $id],
        );
    }

    #[On('{tableName}::excluir-definitivo')]
    public function excluirDefinitivo(int $id): void
    {
        $registro = $this->registroNaLixeira($id);
        $this->authorize('forceDelete', $registro);

        $registro->forceDelete();
        $this->notificarSucesso('Registro excluído definitivamente.');
    }

    /**
     * Classe do model da tabela — precisa usar SoftDeletes e implementar UsaSoftDeletes.
     *
     * @return class-string<Model&UsaSoftDeletes>
     */
    abstract protected function modelClassLixeira(): string;

    /**
     * Hook: mensagem que IMPEDE a exclusão (soft-delete) deste registro, ou null
     * para permitir. Regras de negócio (ex.: não excluir a empresa ativa/última,
     * não excluir a si mesmo) vivem aqui — surgem como toast de erro.
     */
    protected function bloqueioExclusao(Model $registro): ?string
    {
        return null;
    }

    /** Hook: mensagem que IMPEDE a restauração (ex.: e-mail já em uso por um ativo), ou null. */
    protected function bloqueioRestauracao(Model $registro): ?string
    {
        return null;
    }

    /** Texto do confirm de exclusão definitiva. Sobrescreva para avisar de cascata física. */
    protected function textoExcluirDefinitivo(Model $registro): string
    {
        return 'Ação irreversível: o registro é removido do banco de dados.';
    }

    /**
     * Compõe o escopo de lixeira ao datasource: ativos por padrão
     * (SoftDeletingScope vigente); só excluídos quando $verLixeira. Aplicado POR
     * FORA do escopo multi-empresa.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function aplicarLixeira(Builder $query): Builder
    {
        if (! $this->verLixeira) {
            return $query;
        }

        // Equivale a onlyTrashed() — escrito com métodos do Builder base para não
        // depender da macro do SoftDeletes num Builder<TModel> genérico.
        return $query
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull($query->getModel()->getTable() . '.deleted_at');
    }

    // ---- Localização dos registros ---------------------------------------

    /**
     * Registro ativo (não-trashed) por id, respeitando os global scopes (empresa
     * ativa etc.) — impede agir sobre registro de outro tenant.
     */
    private function registroAtivo(int $id): Model
    {
        $classe = $this->modelClassLixeira();

        return $classe::query()->findOrFail($id);
    }

    /** Registro NA lixeira (trashed) por id, ainda sob os demais global scopes. */
    private function registroNaLixeira(int $id): Model&UsaSoftDeletes
    {
        $classe = $this->modelClassLixeira();

        return $classe::query()->onlyTrashed()->findOrFail($id);
    }
}
