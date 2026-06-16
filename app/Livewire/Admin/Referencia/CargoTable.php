<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Cargo;
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

final class CargoTable extends PowerGridComponent
{
    use ComLixeira;
    use WithExport;

    public string $tableName = 'cargos-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.referencia.cargos._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('cargos')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Cargo>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Cargo::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo_cbo')
            ->add('descricao')
            ->add('ativo_label', fn (Cargo $r): string => $r->ativo ? 'Sim' : 'Não');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Código CBO', 'codigo_cbo')
                ->searchable()
                ->sortable(),

            Column::make('Descrição', 'descricao')
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
            Filter::inputText('codigo_cbo')->placeholder('Filtrar por código'),
            Filter::inputText('descricao')->placeholder('Filtrar por descrição'),
            Filter::boolean('ativo'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Cargo) {
            return null;
        }

        return view('livewire.admin.referencia.cargos._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Cargo>
     */
    protected function modelClassLixeira(): string
    {
        return Cargo::class;
    }
}
