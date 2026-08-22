<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use App\Livewire\Concerns\ComAcoesCrud;
use App\Livewire\Concerns\ComLixeira;
use App\Models\Referencia\Cargo;
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

final class CargoTable extends PowerGridComponent
{
    use ComAcoesCrud;
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
                ->includeViewOnTop('livewire.admin.partials.lixeira-toolbar'),
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

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'cargos';
    }

    /** Evento que abre a ficha "Ver" (ComAcoesCrud). */
    protected function eventoVer(): string
    {
        return 'cargos::ver';
    }

    /** Rota de edição do registro (ComAcoesCrud). */
    protected function rotaEditar(Model $row): string
    {
        return route('admin.referencia.cargos.edit', ['cargo' => $row->getKey()]);
    }

    /**
     * @return class-string<Cargo>
     */
    protected function modelClassLixeira(): string
    {
        return Cargo::class;
    }
}
