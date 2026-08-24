<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComAcoesCrud;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Models\Referencia\Moeda;
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

final class MoedaTable extends PowerGridComponent
{
    use ComAcoesCrud;
    use ComLixeira;
    use WithExport;

    public string $tableName = 'moedas-table';

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
            PowerGrid::exportable('moedas')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Moeda>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Moeda::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo_iso')
            ->add('numerico')
            ->add('nome')
            ->add('simbolo')
            ->add('casas_decimais')
            ->add('ativo_label', fn (Moeda $registro): string => $registro->ativo ? 'Ativo' : 'Inativo');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Código ISO', 'codigo_iso')
                ->searchable()
                ->sortable(),

            Column::make('Numérico', 'numerico')
                ->searchable()
                ->sortable(),

            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Símbolo', 'simbolo')
                ->searchable()
                ->sortable(),

            Column::make('Casas decimais', 'casas_decimais')
                ->searchable()
                ->sortable(),

            Column::make('Ativo', 'ativo_label', 'ativo')
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
            Filter::inputText('codigo_iso')->placeholder('Filtrar por código ISO'),
            Filter::inputText('numerico')->placeholder('Filtrar por código numérico'),
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::inputText('simbolo')->placeholder('Filtrar por símbolo'),
            Filter::boolean('ativo'),
        ];
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'moedas';
    }

    /** Evento que abre a ficha "Ver" (ComAcoesCrud). */
    protected function eventoVer(): string
    {
        return 'moedas::ver';
    }

    /** Rota de edição do registro (ComAcoesCrud). */
    protected function rotaEditar(Model $row): string
    {
        return route('admin.referencia.moedas.edit', ['moeda' => $row->getKey()]);
    }

    /**
     * @return class-string<Moeda>
     */
    protected function modelClassLixeira(): string
    {
        return Moeda::class;
    }
}
