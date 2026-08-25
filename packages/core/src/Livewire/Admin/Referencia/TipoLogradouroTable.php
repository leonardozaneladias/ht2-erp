<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Models\Referencia\TipoLogradouro;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class TipoLogradouroTable extends PowerGridComponent
{
    use ComLixeira;
    use WithExport;

    public string $tableName = 'tipos-logradouro-table';

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
            PowerGrid::exportable('tipos_logradouro')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<TipoLogradouro>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(TipoLogradouro::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nome')
            ->add('codigo')
            ->add('abrev')
            ->add('ativo_label', fn (TipoLogradouro $r): string => $r->ativo ? 'Sim' : 'Não');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Código', 'codigo')
                ->searchable()
                ->sortable(),

            Column::make('Abreviação', 'abrev')
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
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::inputText('codigo')->placeholder('Filtrar por código'),
            Filter::inputText('abrev')->placeholder('Filtrar por abreviação'),
            Filter::boolean('ativo')
                ->label('Sim', 'Não'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof TipoLogradouro) {
            return null;
        }

        return view('livewire.admin.referencia.tipos_logradouro._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'tipos_logradouro';
    }

    /**
     * @return class-string<TipoLogradouro>
     */
    protected function modelClassLixeira(): string
    {
        return TipoLogradouro::class;
    }
}
