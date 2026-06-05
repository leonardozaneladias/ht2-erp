<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Admin\AtribuirPerfilEmMassaAction;
use App\Actions\Admin\BulkUserStatusAction;
use App\Actions\Admin\ToggleAdminUserStatusAction;
use App\DTOs\Admin\AtribuicaoPerfilMassaDTO;
use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Services\Admin\HierarchyResolver;
use App\Services\Admin\Security\ControleLockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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
use RuntimeException;
use Spatie\Permission\Models\Role;

final class UsuariosTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'usuarios-table';

    // Perfil selecionado na barra de ações em massa.
    public string $perfilEmMassa = '';

    /**
     * @return array<int, Wireable>
     */
    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns()
                ->includeViewOnTop('livewire.admin.usuarios.usuarios-table-bulk'),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
            PowerGrid::exportable('usuarios-admin')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
        ];
    }

    /**
     * @return Builder<AdminUser>
     */
    public function datasource(): Builder
    {
        return AdminUser::query()->with('roles');
    }

    /**
     * Permite que a busca global alcance o nome dos perfis (roles).
     *
     * @return array<string, array<int, string>>
     */
    public function relationSearch(): array
    {
        return [
            'roles' => ['name'],
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('nome', fn (AdminUser $u): string => $this->renderNome($u))
            ->add('email')
            ->add('perfis', fn (AdminUser $u): string => $this->renderPerfis($u))
            ->add('status', fn (AdminUser $u): string => $this->renderStatus($u))
            ->add('last_login_at_formatted', fn (AdminUser $u): string => $u->last_login_at instanceof Carbon ? $u->last_login_at->format('d/m/Y H:i') : '—');
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

            Column::make('E-mail', 'email')
                ->searchable()
                ->sortable(),

            Column::make('Perfis', 'perfis'),

            Column::make('Status', 'status', 'ativo')
                ->sortable(),

            Column::make('Último login', 'last_login_at_formatted', 'last_login_at')
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
                ->label('Ativo', 'Inativo'),

            Filter::select('perfis', 'perfil')
                ->dataSource($this->opcoesDePerfil())
                ->optionValue('perfil')
                ->optionLabel('perfil')
                ->builder(fn (Builder $query, string $value): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $q): Builder => $q->where('name', $value),
                )),
        ];
    }

    /**
     * @return array<int, Button>
     */
    public function actions(AdminUser $row): array
    {
        $ator = Auth::guard('admin')->user();
        $botoes = [];

        if ($ator?->can('update', $row)) {
            $botoes[] = Button::add('edit')
                ->slot('Editar')
                ->class('btn btn-sm inline-flex items-center gap-x-2 border-default-300 text-default-700 hover:bg-light hover:border-default-400')
                ->route('admin.usuarios.edit', ['usuario' => $row->id])
                ->attributes(['wire:navigate' => '']);
        }

        if ($ator?->can('toggleStatus', $row)) {
            $classeToggle = $row->ativo
                ? 'btn btn-sm inline-flex items-center gap-x-2 bg-warning/15 text-warning hover:bg-warning/25'
                : 'btn btn-sm inline-flex items-center gap-x-2 bg-success/12 text-success hover:bg-success/20';

            $botoes[] = Button::add('toggle')
                ->slot($row->ativo ? 'Desativar' : 'Reativar')
                ->class($classeToggle)
                ->dispatch('usuarios::toggle-status', ['id' => $row->id])
                ->confirm($row->ativo ? 'Desativar usuário?' : 'Reativar usuário?');
        }

        if ($ator?->can('impersonate', $row)) {
            $botoes[] = Button::add('impersonate')
                ->slot('Entrar como')
                ->class('btn btn-sm inline-flex items-center gap-x-2 bg-primary/12 text-primary hover:bg-primary/20')
                ->dispatch('impersonation::abrir', ['id' => $row->id]);
        }

        if ($row->estaBloqueada() && $ator?->can('update', $row)) {
            $botoes[] = Button::add('desbloquear')
                ->slot('Desbloquear')
                ->class('btn btn-sm inline-flex items-center gap-x-2 bg-info/12 text-info hover:bg-info/20')
                ->dispatch('usuarios::desbloquear', ['id' => $row->id]);
        }

        return $botoes;
    }

    #[On('usuarios::toggle-status')]
    public function alternarStatus(int $id, ToggleAdminUserStatusAction $action): void
    {
        $usuario = AdminUser::findOrFail($id);
        $this->authorize('toggleStatus', $usuario);

        try {
            $action->execute($usuario);
            session()->flash('toast.success', 'Status do usuário atualizado.');
        } catch (RuntimeException $e) {
            session()->flash('toast.error', $e->getMessage());
        }
    }

    #[On('usuarios::desbloquear')]
    public function desbloquear(int $id, ControleLockout $lockout): void
    {
        $usuario = AdminUser::findOrFail($id);
        $this->authorize('update', $usuario);

        $lockout->liberar($usuario);
        session()->flash('toast.success', 'Conta desbloqueada.');
    }

    public function atribuirPerfilEmMassa(AtribuirPerfilEmMassaAction $action): void
    {
        $this->authorize('create', AdminUser::class);

        if ($this->checkboxValues === [] || $this->perfilEmMassa === '') {
            session()->flash('toast.error', 'Selecione usuários e um perfil.');

            return;
        }

        try {
            $total = $action->execute(AtribuicaoPerfilMassaDTO::fromArray([
                'adminUserIds' => array_map('intval', $this->checkboxValues),
                'roles' => [$this->perfilEmMassa],
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        $this->limparSelecao();
        session()->flash('toast.success', "Perfil atribuído a {$total} usuário(s).");
    }

    public function alternarStatusEmMassa(bool $ativo, BulkUserStatusAction $action): void
    {
        $this->authorize('create', AdminUser::class);

        if ($this->checkboxValues === []) {
            return;
        }

        try {
            $total = $action->execute(array_map('intval', $this->checkboxValues), $ativo, Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        $this->limparSelecao();
        session()->flash('toast.success', ($ativo ? 'Reativados' : 'Desativados') . " {$total} usuário(s).");
    }

    public function limparSelecao(): void
    {
        $this->checkboxValues = [];
        $this->perfilEmMassa = '';
    }

    /**
     * Perfis que o ator pode atribuir em massa (abaixo da sua hierarquia).
     *
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function perfisAtribuiveis(): array
    {
        $ator = Auth::guard('admin')->user();

        if (! $ator instanceof AdminUser) {
            return [];
        }

        return app(HierarchyResolver::class)->rolesGerenciaveis($ator)
            ->map(static fn (Role $role): array => ['value' => $role->name, 'label' => $role->name])
            ->values()
            ->all();
    }

    /**
     * Opções de perfil para o filtro de coluna (todos os perfis do guard admin).
     *
     * @return list<array{perfil: string}>
     */
    protected function opcoesDePerfil(): array
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->map(static fn (string $name): array => ['perfil' => $name])
            ->all();
    }

    protected function renderNome(AdminUser $u): string
    {
        $nome = (string) $u->getAttribute('nome');

        return Blade::render(
            '<div class="flex items-center gap-2.5">'
            . '<span class="bg-primary/12 text-primary inline-flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold">{{ $iniciais }}</span>'
            . '<span class="font-medium">{{ $nome }}</span>'
            . '</div>',
            ['iniciais' => $this->iniciais($nome), 'nome' => $nome],
        );
    }

    protected function renderStatus(AdminUser $u): string
    {
        if ($u->estaBloqueada()) {
            return Blade::render('<x-shared.badge variant="warning" icon="tabler--lock" size="sm">Bloqueada</x-shared.badge>');
        }

        return Blade::render(
            '<x-shared.badge :variant="$v" :icon="$i" size="sm">{{ $t }}</x-shared.badge>',
            [
                'v' => $u->ativo ? 'success' : 'default',
                'i' => $u->ativo ? 'tabler--circle-check' : 'tabler--circle-x',
                't' => $u->ativo ? 'Ativo' : 'Inativo',
            ],
        );
    }

    protected function renderPerfis(AdminUser $u): string
    {
        $nomes = $u->roles->pluck('name')->all();

        if ($nomes === []) {
            return '<span class="text-default-400">—</span>';
        }

        $visiveis = array_slice($nomes, 0, 2);
        $resto = count($nomes) - count($visiveis);

        return Blade::render(
            '<div class="flex flex-wrap items-center gap-1">'
            . '@foreach ($visiveis as $nome)<x-shared.badge variant="primary" size="sm">{{ $nome }}</x-shared.badge>@endforeach'
            . '@if ($resto > 0)<x-shared.badge variant="default" size="sm">+{{ $resto }}</x-shared.badge>@endif'
            . '</div>',
            ['visiveis' => $visiveis, 'resto' => $resto],
        );
    }

    /**
     * Iniciais (1-2 letras) derivadas do nome, para o avatar da listagem.
     */
    protected function iniciais(string $nome): string
    {
        $partes = array_values(array_filter(preg_split('/\s+/', trim($nome)) ?: []));

        if ($partes === []) {
            return '?';
        }

        $primeira = mb_substr($partes[0], 0, 1);
        $ultima = count($partes) > 1 ? mb_substr($partes[count($partes) - 1], 0, 1) : '';

        return mb_strtoupper($primeira . $ultima);
    }
}
