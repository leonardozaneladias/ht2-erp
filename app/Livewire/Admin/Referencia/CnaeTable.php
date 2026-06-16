<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Cnae;
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

final class CnaeTable extends PowerGridComponent
{
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
                ->includeViewOnTop('livewire.admin.referencia.cnaes._lixeira-toggle'),
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

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Cnae) {
            return null;
        }

        return view('livewire.admin.referencia.cnaes._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Cnae>
     */
    protected function modelClassLixeira(): string
    {
        return Cnae::class;
    }
}
