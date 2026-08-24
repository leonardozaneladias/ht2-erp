<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Referencia;

use HT2ML\Core\Livewire\Concerns\ComAcoesCrud;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Models\Referencia\Estado;
use HT2ML\Core\Models\Referencia\Municipio;
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

final class MunicipioTable extends PowerGridComponent
{
    use ComAcoesCrud;
    use ComLixeira;
    use WithExport;

    public string $tableName = 'municipios-table';

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
            PowerGrid::exportable('municipios')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Municipio>
     */
    public function datasource(): Builder
    {
        // Eager-load com withTrashed: o nome da UF aparece mesmo se o estado
        // estiver na lixeira (a FK continua válida), e o relacionamento nunca é nulo.
        return $this->aplicarLixeira(
            Municipio::query()->with(['estado' => fn ($query) => $query->withTrashed()]),
        );
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo_ibge')
            ->add('nome')
            ->add('estado_nome', fn (Municipio $registro): string => $registro->estado->nome);
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

            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Estado', 'estado_nome', 'estado_id')
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
            Filter::inputText('nome')->placeholder('Filtrar por nome'),
            Filter::multiSelect('estado_id', 'estado_id')
                ->dataSource($this->opcoesEstado())
                ->optionValue('id')
                ->optionLabel('nome'),
        ];
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'municipios';
    }

    /** Evento que abre a ficha "Ver" (ComAcoesCrud). */
    protected function eventoVer(): string
    {
        return 'municipios::ver';
    }

    /** Rota de edição do registro (ComAcoesCrud). */
    protected function rotaEditar(Model $row): string
    {
        return route('admin.referencia.municipios.edit', ['municipio' => $row->getKey()]);
    }

    /**
     * @return class-string<Municipio>
     */
    protected function modelClassLixeira(): string
    {
        return Municipio::class;
    }

    /**
     * @return array<int, array{id: int, nome: string}>
     */
    protected function opcoesEstado(): array
    {
        return Estado::query()
            ->orderBy('nome')
            ->get(['id', 'nome'])
            ->map(fn (Estado $estado): array => ['id' => $estado->id, 'nome' => $estado->nome])
            ->all();
    }
}
