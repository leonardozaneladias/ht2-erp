<?php

declare(strict_types=1);

namespace HT2ML\Rh\Livewire\Funcionarios;

use HT2ML\Core\DTOs\Admin\Export\ExportavelDTO;
use HT2ML\Core\Livewire\Concerns\ComLixeira;
use HT2ML\Core\Livewire\Concerns\ExportaPdf;
use HT2ML\Core\Livewire\Concerns\FiltraPorMultiEmpresa;
use HT2ML\Rh\Enums\StatusFuncionario;
use HT2ML\Rh\Models\Funcionario;
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

final class FuncionarioTable extends PowerGridComponent
{
    use ComLixeira;
    use ExportaPdf;
    use FiltraPorMultiEmpresa;
    use WithExport;

    public string $tableName = 'rh.funcionarios-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('rh::livewire.funcionarios._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('funcionarios')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Funcionario>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira($this->aplicarEscopoMultiEmpresa(Funcionario::query()));
    }

    public function fields(): PowerGridFields
    {
        return $this->camposMultiEmpresa(PowerGrid::fields()
            ->add('id')
            ->add('nome')
            ->add('cpf')
            ->add('cargo')
            ->add('salario')
            ->add('admissao')
            ->add('status_badge', fn (Funcionario $registro): string => $this->renderStatus($registro)));
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

            Column::make('Cpf', 'cpf')
                ->searchable()
                ->sortable(),

            Column::make('Cargo', 'cargo')
                ->searchable()
                ->sortable(),

            Column::make('Salario', 'salario')
                ->searchable()
                ->sortable(),

            Column::make('Admissao', 'admissao')
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
            Filter::inputText('cpf')->placeholder('Filtrar por Cpf'),
            Filter::inputText('cargo')->placeholder('Filtrar por Cargo'),
            Filter::multiSelect('status', 'status')
                ->dataSource(StatusFuncionario::options())
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Funcionario) {
            return null;
        }

        return view('rh::livewire.funcionarios._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    /**
     * @return class-string<Funcionario>
     */
    protected function modelClassLixeira(): string
    {
        return Funcionario::class;
    }

    protected function permissaoListagem(): string
    {
        // Derivado de permissaoBase(), nunca literal: as duas fórmulas já
        // divergiram uma vez. O gerador emitia snakePlural().'.listar' e o
        // catálogo usava permissaoBase().'.listar', então esta tabela exigia uma
        // permissão inexistente — e empresasElegiveis() negava toda empresa a
        // quem não fosse super-admin, desligando o filtro multiempresa em
        // silêncio. Ver tests/Feature/Modules/PermissaoDeListagemTest.php.
        return $this->permissaoBase() . '.listar';
    }

    /** Prefixo das permissões do recurso (ComLixeira). */
    protected function permissaoBase(): string
    {
        return 'rh.funcionarios';
    }

    /**
     * Dados da listagem para exportação em PDF (trait ExportaPdf).
     */
    protected function dadosParaExportacao(): ExportavelDTO
    {
        $linhas = $this->linhasParaExportacao()
            ->map(fn (Funcionario $registro): array => [
                ...$this->linhaMultiEmpresa($registro),
                (string) $registro->nome,
                (string) $registro->cpf,
                (string) $registro->cargo,
                (string) $registro->salario,
                (string) $registro->admissao,
                $registro->status->label(),
            ])
            ->values()
            ->all();

        return new ExportavelDTO('Funcionarios', [...$this->cabecalhosMultiEmpresa(), 'Nome', 'Cpf', 'Cargo', 'Salario', 'Admissao', 'Status'], $linhas);
    }

    protected function renderStatus(Funcionario $registro): string
    {
        return Blade::render(
            '<x-shared.badge :variant="$v" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $registro->status->variant(), 't' => $registro->status->label()],
        );
    }
}
