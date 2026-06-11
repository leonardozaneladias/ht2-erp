<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Empresas;

use App\Models\Empresa;
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

final class EmpresasTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'empresas-table';

    public function noDataLabel(): View
    {
        $podeCriar = auth('admin')->user()?->can('empresas.criar') ?? false;

        return view('admin.partials.powergrid-empty', [
            'icon' => 'tabler--building-off',
            'titulo' => 'Nenhuma empresa encontrada',
            'descricao' => 'Cadastre a primeira empresa para começar a operar.',
            'ctaUrl' => $podeCriar ? route('admin.empresas.create') : null,
            'ctaLabel' => $podeCriar ? 'Nova empresa' : null,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('empresas')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Empresa>
     */
    public function datasource(): Builder
    {
        return Empresa::query()->withCount('filiais');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('nome')
            ->add('cnpj_formatado', fn (Empresa $e): string => $e->cnpj !== null && $e->cnpj !== '' ? $e->cnpj : '—')
            ->add('filiais_count')
            ->add('status', fn (Empresa $e): string => $this->renderStatus($e));
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

            Column::make('CNPJ', 'cnpj_formatado', 'cnpj')
                ->searchable()
                ->sortable(),

            Column::make('Filiais', 'filiais_count'),

            Column::make('Status', 'status', 'ativo')
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
            Filter::inputText('nome')
                ->placeholder('Filtrar por nome'),

            Filter::boolean('ativo')
                ->label('Ativa', 'Inativa'),
        ];
    }

    /**
     * Renderiza a coluna de acoes como um unico menu (kebab). A logica de
     * permissao vive na view `livewire.admin.empresas._acoes`.
     */
    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Empresa) {
            return null;
        }

        return view('livewire.admin.empresas._acoes', ['row' => $row]);
    }

    protected function renderStatus(Empresa $e): string
    {
        return Blade::render(
            '<x-shared.badge :variant="$v" :icon="$i" size="sm">{{ $t }}</x-shared.badge>',
            [
                'v' => $e->ativo ? 'success' : 'default',
                'i' => $e->ativo ? 'tabler--circle-check' : 'tabler--circle-x',
                't' => $e->ativo ? 'Ativa' : 'Inativa',
            ],
        );
    }
}
