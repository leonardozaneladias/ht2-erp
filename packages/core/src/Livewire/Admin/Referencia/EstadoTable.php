<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Enums\Referencia\RegiaoBrasil;
use HT2ML\Core\Livewire\Concerns\ComAcoesCrud;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Models\Referencia\Estado;
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

final class EstadoTable extends PowerGridComponent
{
    use ComAcoesCrud;
    use ComLixeira;
    use WithExport;

    public string $tableName = 'estados-table';

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
            PowerGrid::exportable('estados')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Estado>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Estado::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo_ibge')
            ->add('sigla')
            ->add('nome')
            ->add('regiao_label', fn (Estado $registro): string => $registro->regiao->label());
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Código IBGE', 'codigo_ibge')
                ->searchable()
                ->sortable(),

            Column::make('Sigla', 'sigla')
                ->searchable()
                ->sortable(),

            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Região', 'regiao_label', 'regiao')
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
            Filter::inputText('codigo_ibge')->placeholder('Filtrar por código'),
            Filter::inputText('sigla')->placeholder('Filtrar por sigla'),
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::multiSelect('regiao', 'regiao')
                ->dataSource($this->opcoesRegiao())
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'estados';
    }

    /** Evento que abre a ficha "Ver" (ComAcoesCrud). */
    protected function eventoVer(): string
    {
        return 'estados::ver';
    }

    /** Rota de edição do registro (ComAcoesCrud). */
    protected function rotaEditar(Model $row): string
    {
        return route('admin.referencia.estados.edit', ['estado' => $row->getKey()]);
    }

    /**
     * @return class-string<Estado>
     */
    protected function modelClassLixeira(): string
    {
        return Estado::class;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function opcoesRegiao(): array
    {
        return array_map(
            fn (RegiaoBrasil $regiao): array => ['value' => $regiao->value, 'label' => $regiao->label()],
            RegiaoBrasil::cases(),
        );
    }
}
