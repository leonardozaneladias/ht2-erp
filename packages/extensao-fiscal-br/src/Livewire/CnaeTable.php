<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Livewire;

use HT2ML\Core\Livewire\Concerns\ComAcoesCrud;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\FiscalBr\Models\Cnae;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class CnaeTable extends PowerGridComponent
{
    use ComAcoesCrud;
    use ComLixeira;
    use WithExport;

    public string $tableName = 'cnaes-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.partials.lixeira-toolbar'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('cnaes')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Cnae>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Cnae::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo')
            ->add('descricao')
            ->add('secao')
            ->add('divisao')
            ->add('grupo')
            ->add('classe');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Código', 'codigo')
                ->searchable()
                ->sortable(),

            Column::make('Descrição', 'descricao')
                ->searchable()
                ->sortable(),

            Column::make('Seção', 'secao')
                ->sortable(),

            Column::make('Divisão', 'divisao')
                ->sortable(),

            Column::make('Grupo', 'grupo')
                ->sortable(),

            Column::make('Classe', 'classe')
                ->sortable(),

            Column::action('Ações'),
        ];
    }

    /**
     * @return array<int, FilterBase>
     */
    public function filters(): array
    {
        return [
            Filter::inputText('codigo')->placeholder('Filtrar por código'),
            Filter::inputText('descricao')->placeholder('Filtrar por descrição'),
        ];
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'cnaes';
    }

    /** Evento que abre a ficha "Ver" (ComAcoesCrud). */
    protected function eventoVer(): string
    {
        return 'cnaes::ver';
    }

    /** Rota de edição do registro (ComAcoesCrud). */
    protected function rotaEditar(Model $row): string
    {
        return route('admin.referencia.cnaes.edit', ['cnae' => $row->getKey()]);
    }

    /**
     * @return class-string<Cnae>
     */
    protected function modelClassLixeira(): string
    {
        return Cnae::class;
    }
}
