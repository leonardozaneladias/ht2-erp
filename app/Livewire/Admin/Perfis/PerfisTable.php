<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Perfis;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Wireable;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\Filters\FilterBase;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use Spatie\Permission\Models\Role;

final class PerfisTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'perfis-table';

    public string $sortField = 'nivel';

    public string $sortDirection = 'desc';

    /**
     * @return array<int, Wireable>
     */
    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('perfis-admin')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<Role>
     */
    public function datasource(): Builder
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->withCount(['permissions', 'users']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('perfil', fn (Role $r): string => $this->renderPerfil($r))
            ->add('nivel', fn (Role $r): string => Blade::render('<x-shared.badge variant="neutral">{{ $n }}</x-shared.badge>', ['n' => $r->getAttribute('nivel')]))
            ->add('permissions_count')
            ->add('users_count');
    }

    /**
     * @return array<int, Column>
     */
    public function columns(): array
    {
        return [
            Column::make('Perfil', 'perfil', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Nível', 'nivel')
                ->sortable(),

            Column::make('Permissões', 'permissions_count')
                ->sortable(),

            Column::make('Usuários', 'users_count')
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
            Filter::inputText('perfil', 'name')
                ->placeholder('Filtrar por nome'),
        ];
    }

    /**
     * @return array<int, Button>
     */
    public function actions(Role $row): array
    {
        $ator = Auth::guard('admin')->user();
        $botoes = [];

        if ($ator?->can('update', $row)) {
            $botoes[] = Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-sm inline-flex items-center gap-x-2 border-default-300 text-default-700 hover:bg-light hover:border-default-400')
                ->route('admin.perfis.edit', ['perfil' => $row->id])
                ->attributes(['wire:navigate' => '']);
        }

        return $botoes;
    }

    protected function renderPerfil(Role $r): string
    {
        $descricao = (string) ($r->getAttribute('descricao') ?? '');

        return Blade::render(
            '<div class="font-medium">{{ $nome }}</div>@if (filled($descricao))<div class="text-default-500 text-xs">{{ $descricao }}</div>@endif',
            ['nome' => $r->name, 'descricao' => $descricao],
        );
    }
}
