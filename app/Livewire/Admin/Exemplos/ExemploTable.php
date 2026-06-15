<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Exemplos;

use App\DTOs\Admin\Export\ExportavelDTO;
use App\Enums\StatusExemplo;
use App\Livewire\Concerns\ComLixeira;
use App\Livewire\Concerns\ExportaPdf;
use App\Livewire\Concerns\FiltraPorMultiEmpresa;
use App\Models\Exemplo;
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

final class ExemploTable extends PowerGridComponent
{
    use ComLixeira;
    use ExportaPdf;
    use FiltraPorMultiEmpresa;
    use WithExport;

    public string $tableName = 'exemplos-table';

    /**
     * @return array<int, mixed>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.exemplos._lixeira-toggle'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('exemplos')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Exemplo>
     */
    public function datasource(): Builder
    {
        return $this->aplicarLixeira($this->aplicarEscopoMultiEmpresa(Exemplo::query()));
    }

    public function fields(): PowerGridFields
    {
        return $this->camposMultiEmpresa(
            PowerGrid::fields()
                ->add('id')
                ->add('nome')
                ->add('slug')
                ->add('site')
                ->add('email')
                ->add('telefone')
                ->add('cep')
                ->add('cnpj')
                ->add('cpf')
                ->add('preco')
                ->add('custo')
                ->add('quantidade')
                ->add('cor')
                ->add('categoria')
                ->add('destaque')
                ->add('data_inicio')
                ->add('publicado_em')
                ->add('status_badge', fn (Exemplo $registro): string => $this->renderStatus($registro)),
        );
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

            Column::make('Slug', 'slug')
                ->searchable()
                ->sortable(),

            Column::make('Site', 'site')
                ->searchable()
                ->sortable(),

            Column::make('Email', 'email')
                ->searchable()
                ->sortable(),

            Column::make('Telefone', 'telefone')
                ->searchable()
                ->sortable(),

            Column::make('Cep', 'cep')
                ->searchable()
                ->sortable(),

            Column::make('Cnpj', 'cnpj')
                ->searchable()
                ->sortable(),

            Column::make('Cpf', 'cpf')
                ->searchable()
                ->sortable(),

            Column::make('Preco', 'preco')
                ->searchable()
                ->sortable(),

            Column::make('Custo', 'custo')
                ->searchable()
                ->sortable(),

            Column::make('Quantidade', 'quantidade')
                ->searchable()
                ->sortable(),

            Column::make('Cor', 'cor')
                ->searchable()
                ->sortable(),

            Column::make('Categoria', 'categoria')
                ->searchable()
                ->sortable(),

            Column::make('Destaque', 'destaque')
                ->searchable()
                ->sortable(),

            Column::make('Data inicio', 'data_inicio')
                ->searchable()
                ->sortable(),

            Column::make('Publicado em', 'publicado_em')
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
            Filter::inputText('slug')->placeholder('Filtrar por Slug'),
            Filter::inputText('site')->placeholder('Filtrar por Site'),
            Filter::inputText('email')->placeholder('Filtrar por Email'),
            Filter::inputText('telefone')->placeholder('Filtrar por Telefone'),
            Filter::inputText('cep')->placeholder('Filtrar por Cep'),
            Filter::inputText('cnpj')->placeholder('Filtrar por Cnpj'),
            Filter::inputText('cpf')->placeholder('Filtrar por Cpf'),
            Filter::boolean('destaque'),
            Filter::multiSelect('status', 'status')
                ->dataSource(StatusExemplo::options())
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof Exemplo) {
            return null;
        }

        return view('livewire.admin.exemplos._acoes', ['row' => $row, 'verLixeira' => $this->verLixeira]);
    }

    protected function permissaoListagem(): string
    {
        return 'exemplos.listar';
    }

    /**
     * @return class-string<Exemplo>
     */
    protected function modeloMultiEmpresa(): string
    {
        return Exemplo::class;
    }

    /**
     * @return class-string<Exemplo>
     */
    protected function modelClassLixeira(): string
    {
        return Exemplo::class;
    }

    /**
     * Dados da listagem para exportação em PDF (trait ExportaPdf).
     */
    protected function dadosParaExportacao(): ExportavelDTO
    {
        $linhas = $this->datasource()->get()
            ->map(fn (Exemplo $registro): array => [
                ...$this->linhaMultiEmpresa($registro),
                (string) $registro->nome,
                (string) $registro->slug,
                (string) $registro->site,
                (string) $registro->email,
                (string) $registro->telefone,
                (string) $registro->cep,
                (string) $registro->cnpj,
                (string) $registro->cpf,
                (string) $registro->preco,
                (string) $registro->custo,
                (string) $registro->quantidade,
                (string) $registro->cor,
                (string) $registro->categoria,
                (string) $registro->destaque,
                (string) $registro->data_inicio,
                (string) $registro->publicado_em,
                $registro->status->label(),
            ])
            ->values()
            ->all();

        return new ExportavelDTO(
            'Exemplos',
            [...$this->cabecalhosMultiEmpresa(), 'Nome', 'Slug', 'Site', 'Email', 'Telefone', 'Cep', 'Cnpj', 'Cpf', 'Preco', 'Custo', 'Quantidade', 'Cor', 'Categoria', 'Destaque', 'Data inicio', 'Publicado em', 'Status'],
            $linhas,
        );
    }

    protected function renderStatus(Exemplo $registro): string
    {
        return Blade::render(
            '<x-shared.badge :variant="$v" size="sm">{{ $t }}</x-shared.badge>',
            ['v' => $registro->status->variant(), 't' => $registro->status->label()],
        );
    }
}
