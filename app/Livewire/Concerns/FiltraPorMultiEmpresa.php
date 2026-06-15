<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\AdminUser;
use App\Models\Empresa;
use App\Services\Admin\AccessResolver;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

/**
 * Dá a uma tabela PowerGrid de um model tenant (BelongsToEmpresa) um filtro
 * multi-empresa seguro. Por padrão a listagem mostra só a empresa ativa (global
 * scope); usuários com a capacidade `listagens.multi_empresa` e acesso a 2+
 * empresas ganham um multiselect para incluir outras empresas — limitado ao RBAC
 * estrito (a permissão `listar` do módulo naquela empresa). Uma coluna Empresa
 * passa a identificar cada registro.
 *
 * A tabela informa a permissão via permissaoListagem() e compõe os helpers em
 * datasource()/fields()/columns()/filters():
 *
 *   public function datasource(): Builder { return $this->aplicarEscopoMultiEmpresa(Produto::query()); }
 *   public function fields(): PowerGridFields { return $this->camposMultiEmpresa(PowerGrid::fields()->add('id')...); }
 *   public function columns(): array { return [...$this->colunasMultiEmpresa(), Column::make('Nome', 'nome'), ...]; }
 *   public function filters(): array { return [...$this->filtrosMultiEmpresa(), Filter::inputText('nome'), ...]; }
 *
 * @mixin \PowerComponents\LivewirePowerGrid\PowerGridComponent
 */
trait FiltraPorMultiEmpresa
{
    /** @var Collection<int, Empresa>|null */
    private ?Collection $empresasElegiveisCache = null;

    /**
     * Permissão `listar` do módulo (ex.: 'produtos.listar'). É o que o RBAC
     * estrito checa por empresa para decidir a elegibilidade de cada empresa.
     */
    abstract protected function permissaoListagem(): string;

    /**
     * Aplica o escopo multi-empresa ao datasource. Sem capacidade/seleção, mantém
     * o global scope vigente (empresa ativa). Com seleção, troca o global scope
     * pela intersecção `selecionadas ∩ elegíveis` — valores do cliente nunca
     * ampliam o escopo.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function aplicarEscopoMultiEmpresa(Builder $query): Builder
    {
        if (! $this->multiEmpresaAtivo()) {
            return $query;
        }

        // A coluna Empresa é exibida sempre que o multi-empresa está ativo; o
        // eager-load evita N+1 mesmo sem seleção (escopo = empresa ativa).
        $query->with('empresa');

        $empresaIds = $this->empresasSelecionadas();

        if ($empresaIds === []) {
            return $query;
        }

        $tabela = $query->getModel()->getTable();

        $query->withoutGlobalScope('empresa')->whereIn($tabela . '.empresa_id', $empresaIds);

        return $query;
    }

    protected function camposMultiEmpresa(PowerGridFields $fields): PowerGridFields
    {
        if ($this->multiEmpresaAtivo()) {
            $fields->add('empresa_nome', fn (Model $registro): string => $this->nomeRelacao($registro, 'empresa'));
        }

        return $fields;
    }

    /**
     * @return array<int, Column>
     */
    protected function colunasMultiEmpresa(): array
    {
        if (! $this->multiEmpresaAtivo()) {
            return [];
        }

        return [
            Column::make('Empresa', 'empresa_nome', 'empresa_id'),
        ];
    }

    /**
     * @return array<int, FilterBase>
     */
    protected function filtrosMultiEmpresa(): array
    {
        if (! $this->multiEmpresaAtivo()) {
            return [];
        }

        return [
            Filter::multiSelect('empresa_nome', 'empresa_id')
                ->dataSource($this->opcoesEmpresa())
                ->optionValue('id')
                ->optionLabel('nome')
                ->builder($this->builderNoop()),
        ];
    }

    /**
     * Recurso disponível: usuário com a capacidade e acesso (com a permissão
     * `listar`) a 2+ empresas. Com 0/1 empresa elegível, comporta-se como hoje.
     */
    protected function multiEmpresaAtivo(): bool
    {
        $user = $this->usuarioMultiEmpresa();

        if (! $user instanceof AdminUser || ! $user->can('listagens.multi_empresa')) {
            return false;
        }

        return $this->empresasElegiveis()->count() > 1;
    }

    /**
     * Empresas onde o usuário pode listar este módulo (RBAC estrito por empresa).
     * Memoizada por request.
     *
     * @return Collection<int, Empresa>
     */
    protected function empresasElegiveis(): Collection
    {
        if ($this->empresasElegiveisCache instanceof Collection) {
            return $this->empresasElegiveisCache;
        }

        $user = $this->usuarioMultiEmpresa();

        if (! $user instanceof AdminUser) {
            return $this->empresasElegiveisCache = collect();
        }

        $resolver = app(AccessResolver::class);
        $ability = $this->permissaoListagem();

        return $this->empresasElegiveisCache = app(TenantResolver::class)
            ->empresasDisponiveis($user)
            ->filter(fn (Empresa $empresa): bool => $resolver->permiteNaEmpresa($user, $ability, (int) $empresa->getKey()))
            ->values();
    }

    /**
     * IDs de empresa selecionados no filtro ∩ elegíveis — a intersecção é a
     * garantia de segurança contra injeção de empresa_id pelo cliente.
     *
     * @return list<int>
     */
    private function empresasSelecionadas(): array
    {
        $brutos = (array) data_get($this->filters, 'multi_select.empresa_id', []);

        $selecionados = array_map('intval', array_filter(
            $brutos,
            static fn (mixed $v): bool => $v !== '' && $v !== null,
        ));

        $elegiveis = $this->empresasElegiveis()
            ->map(static fn (Empresa $e): int => (int) $e->getKey())
            ->all();

        return array_values(array_intersect($selecionados, $elegiveis));
    }

    /**
     * @return list<array{id: int, nome: string}>
     */
    private function opcoesEmpresa(): array
    {
        return $this->empresasElegiveis()
            ->map(static fn (Empresa $e): array => [
                'id' => (int) $e->getKey(),
                'nome' => (string) $e->getAttribute('nome'),
            ])
            ->all();
    }

    /**
     * Builder no-op para os filtros multiselect: o escopo real é aplicado em
     * datasource() (após withoutGlobalScope), então o PowerGrid não deve tocar a
     * query aqui — senão colidiria com o global scope da empresa ativa.
     */
    private function builderNoop(): Closure
    {
        return static fn (Builder $query): Builder => $query;
    }

    private function nomeRelacao(Model $registro, string $relacao): string
    {
        $relacionado = $registro->getRelationValue($relacao);

        return $relacionado instanceof Model
            ? (string) ($relacionado->getAttribute('nome') ?? '—')
            : '—';
    }

    private function usuarioMultiEmpresa(): ?AdminUser
    {
        $user = auth('admin')->user();

        return $user instanceof AdminUser ? $user : null;
    }
}
