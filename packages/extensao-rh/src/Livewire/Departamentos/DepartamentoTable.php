<?php

declare(strict_types=1);

namespace HT2ML\Rh\Livewire\Departamentos;

use App\DTOs\Admin\Export\ExportavelDTO;
use App\Livewire\Concerns\ComLixeira;
use App\Livewire\Concerns\ExportaPdf;
use App\Livewire\Concerns\FiltraPorMultiEmpresa;
use HT2ML\Rh\Enums\StatusDepartamento;
use HT2ML\Rh\Models\Departamento;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class DepartamentoTable extends PowerGridComponent
{
    use ComLixeira;
    use ExportaPdf;
    use FiltraPorMultiEmpresa;
    use WithExport;

    public string $tableName = 'rh.departamentos-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('rh::livewire.departamentos._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('departamentos')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Departamento>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira($this->aplicarEscopoMultiEmpresa(Departamento::query()));
    }

    public function fields(): PowerGridFields
    {
        return $this->camposMultiEmpresa(PowerGrid::fields()
            ->add('id')
            ->add('nome')
            ->add('sigla')
            ->add('status_badge', fn (Departamento $registro): string => $this->renderStatus($registro)));
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            ...$this->colunasMultiEmpresa(),
            Column::make('Nome', 'nome')
                ->searchable()
                ->sortable(),

            Column::make('Sigla', 'sigla')
                ->searchable()
                ->sortable(),

            Column::make('Status', 'status_badge', 'status')
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
            ...$this->filtrosMultiEmpresa(),
            Filter::inputText('nome')->placeholder('Filtrar por Nome'),
            Filter::inputText('sigla')->placeholder('Filtrar por Sigla'),
            Filter::multiSelect('status', 'status')
                ->dataSource(StatusDepartamento::options())
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Departamento) {
            return null;
        }

        return view('rh::livewire.departamentos._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Departamento>
     */
    protected function modelClassLixeira(): string
    {
        return Departamento::class;
    }

    protected function permissaoListagem(): string
    {
        return 'departamentos.listar';
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'rh.departamentos';
    }

    /**
     * Dados da listagem para exportação em PDF (trait ExportaPdf).
     */
    protected function dadosParaExportacao(): ExportavelDTO
    {
        $linhas = $this->datasource()->get()
            ->map(fn (Departamento $registro): array => [
                ...$this->linhaMultiEmpresa($registro),
                (string) $registro->nome,
                (string) $registro->sigla,
                $registro->status->label(),
            ])
            ->values()
            ->all();

        return new ExportavelDTO('Departamentos', [...$this->cabecalhosMultiEmpresa(), 'Nome', 'Sigla', 'Status'], $linhas);
    }

    protected function renderStatus(Departamento $registro): string
    {
        return Blade::render(
            '<x-shared.badge :variant="$v" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $registro->status->variant(), 't' => $registro->status->label()],
        );
    }
}
