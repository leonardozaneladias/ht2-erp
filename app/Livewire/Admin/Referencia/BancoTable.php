<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Banco;
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

final class BancoTable extends PowerGridComponent
{
    use ComLixeira;
    use WithExport;

    public string $tableName = 'bancos-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.referencia.bancos._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('bancos')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Banco>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Banco::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('ispb')
            ->add('codigo_compe')
            ->add('nome')
            ->add('nome_completo')
            ->add('ativo_label', fn (Banco $registro): string => $registro->ativo ? 'Sim' : 'Não');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('ISPB', 'ispb')
                ->searchable()
                ->sortable(),

            Column::make('COMPE', 'codigo_compe')
                ->searchable()
                ->sortable(),

            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Nome completo', 'nome_completo')
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
            Filter::inputText('ispb')->placeholder('Filtrar por ISPB'),
            Filter::inputText('codigo_compe')->placeholder('Filtrar por COMPE'),
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::inputText('nome_completo')->placeholder('Filtrar por nome completo'),
            Filter::boolean('ativo'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Banco) {
            return null;
        }

        return view('livewire.admin.referencia.bancos._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Banco>
     */
    protected function modelClassLixeira(): string
    {
        return Banco::class;
    }
}
