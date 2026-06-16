<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Enums\Referencia\TipoCfop;
use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Cfop;
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

final class CfopTable extends PowerGridComponent
{
    use ComLixeira;
    use WithExport;

    public string $tableName = 'cfops-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.referencia.cfops._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('cfops')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Cfop>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira(Cfop::query());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo')
            ->add('descricao')
            ->add('tipo_label', fn (Cfop $registro): string => $registro->tipo->label())
            ->add('aplicacao');
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

            Column::make('Tipo', 'tipo_label', 'tipo')
                ->sortable(),

            Column::make('Aplicação', 'aplicacao')
                ->searchable()
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
            Filter::multiSelect('tipo', 'tipo')
                ->dataSource($this->opcoesTipo())
                ->optionValue('value')
                ->optionLabel('label'),
            Filter::inputText('aplicacao')->placeholder('Filtrar por aplicação'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Cfop) {
            return null;
        }

        return view('livewire.admin.referencia.cfops._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Cfop>
     */
    protected function modelClassLixeira(): string
    {
        return Cfop::class;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function opcoesTipo(): array
    {
        return array_map(
            fn (TipoCfop $tipo): array => ['value' => $tipo->value, 'label' => $tipo->label()],
            TipoCfop::cases(),
        );
    }
}
