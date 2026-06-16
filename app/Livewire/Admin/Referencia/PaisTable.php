<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Pais;
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

final class PaisTable extends PowerGridComponent
{
    use ComLixeira;
    use WithExport;

    public string $tableName = 'paises-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.referencia.paises._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('paises')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Pais>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Pais::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo_iso2')
            ->add('codigo_iso3')
            ->add('codigo_numerico')
            ->add('nome')
            ->add('ativo_label', fn (Pais $r): string => $r->ativo ? 'Sim' : 'Não');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Código ISO2', 'codigo_iso2')
                ->searchable()
                ->sortable(),

            Column::make('Código ISO3', 'codigo_iso3')
                ->searchable()
                ->sortable(),

            Column::make('Código numérico', 'codigo_numerico')
                ->searchable()
                ->sortable(),

            Column::make('Nome', 'nome')
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
            Filter::inputText('codigo_iso2')->placeholder('Filtrar por código ISO2'),
            Filter::inputText('codigo_iso3')->placeholder('Filtrar por código ISO3'),
            Filter::inputText('codigo_numerico')->placeholder('Filtrar por código numérico'),
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::boolean('ativo'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Pais) {
            return null;
        }

        return view('livewire.admin.referencia.paises._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Pais>
     */
    protected function modelClassLixeira(): string
    {
        return Pais::class;
    }
}
