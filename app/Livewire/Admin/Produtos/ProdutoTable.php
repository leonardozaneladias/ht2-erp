<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Produtos;

use App\DTOs\Admin\Export\ExportavelDTO;
use App\Enums\StatusProduto;
use App\Livewire\Concerns\ExportaPdf;
use App\Models\Produto;
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

final class ProdutoTable extends PowerGridComponent
{
    use ExportaPdf;
    use WithExport;

    public string $tableName = 'produtos-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.produtos._export-pdf'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('produtos')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Produto>
     */
    public function datasource(): Builder
    {
        return Produto::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nome')
            ->add('sku')
            ->add('preco')
            ->add('status_badge', fn (Produto $registro): string => $this->renderStatus($registro));
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

            Column::make('Sku', 'sku')
                ->searchable()
                ->sortable(),

            Column::make('Preco', 'preco')
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
            Filter::inputText('nome')->placeholder('Filtrar por Nome'),
            Filter::inputText('sku')->placeholder('Filtrar por Sku'),
            Filter::multiSelect('status', 'status')
                ->dataSource(StatusProduto::options())
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Produto) {
            return null;
        }

        return view('livewire.admin.produtos._acoes', ['row' => $row]);
    }

    /**
     * Dados da listagem para exportação em PDF (trait ExportaPdf).
     */
    protected function dadosParaExportacao(): ExportavelDTO
    {
        $linhas = $this->datasource()->get()
            ->map(fn (Produto $registro): array => [
                (string) $registro->nome,
                (string) $registro->sku,
                (string) $registro->preco,
                $registro->status->label(),
            ])
            ->values()
            ->all();

        return new ExportavelDTO('Produtos', ['Nome', 'Sku', 'Preco', 'Status'], $linhas);
    }

    protected function renderStatus(Produto $registro): string
    {
        return Blade::render(
            '<x-shared.badge :variant="$v" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $registro->status->variant(), 't' => $registro->status->label()],
        );
    }
}
