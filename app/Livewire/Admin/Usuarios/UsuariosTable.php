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
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Wireable;
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

    public function noDataLabel(): View
    {
        $podeCriar = auth('admin')->user()?->can('usuarios.criar') ?? false;

        return view('admin.partials.powergrid-empty', [
            'icon' => 'tabler--users',
            'titulo' => 'Nenhum usuário encontrado',
            'descricao' => 'Convide o primeiro usuário para acessar o painel.',
            'ctaUrl' => $podeCriar ? route('admin.usuarios.create') : null,
            'ctaLabel' => $podeCriar ? 'Novo usuário' : null,
        ]);
    }

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
     * Renderiza a coluna de acoes como um unico menu (kebab), substituindo a
     * lista de botoes soltos. A logica de permissao vive na view
     * `livewire.admin.usuarios._acoes`. Retorna null para usuarios anonimizados
     * (sem acoes disponiveis).
     */
    public function actionsFromView(mixed $row): ?View
    {
        if (! $row instanceof AdminUser || $row->estaAnonimizado()) {
            return null;
        }

        return view('livewire.admin.usuarios._acoes', ['row' => $row]);
    }

    /**
     * Abre a confirmação temática (bridge SweetAlert2) antes de alternar o
     * status. Feito no servidor para evitar montar JSON/strings dentro de
     * atributos Blade (onde diretivas não compilam). Ao confirmar, o bridge
     * dispara `usuarios::toggle-status`.
     */
    public function solicitarToggleStatus(int $id): void
    {
        $usuario = AdminUser::findOrFail($id);
        $this->authorize('toggleStatus', $usuario);

        $this->dispatch(
            'confirm',
            title: $usuario->ativo ? 'Desativar usuário?' : 'Reativar usuário?',
            text: $usuario->ativo
                ? 'O usuário perderá o acesso ao painel enquanto estiver inativo.'
                : 'O usuário poderá acessar o painel novamente.',
            destructive: $usuario->ativo,
            onConfirm: 'usuarios::toggle-status',
            params: ['id' => $id],
        );
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

    public function solicitarAtribuirPerfilEmMassa(): void
    {
        if ($this->checkboxValues === [] || $this->perfilEmMassa === '') {
            session()->flash('toast.error', 'Selecione usuários e um perfil.');

            return;
        }

        $this->dispatch(
            'confirm',
            title: 'Atribuir perfil em massa?',
            text: sprintf('O perfil "%s" será atribuído a %d usuário(s).', $this->perfilEmMassa, count($this->checkboxValues)),
            destructive: false,
            onConfirm: 'usuarios::atribuir-perfil-massa',
        );
    }

    #[On('usuarios::atribuir-perfil-massa')]
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

    public function solicitarAlternarStatusEmMassa(bool $ativo): void
    {
        if ($this->checkboxValues === []) {
            return;
        }

        $total = count($this->checkboxValues);

        $this->dispatch(
            'confirm',
            title: $ativo ? 'Reativar usuários selecionados?' : 'Desativar usuários selecionados?',
            text: $ativo
                ? "{$total} usuário(s) poderão acessar o painel novamente."
                : "{$total} usuário(s) perderão o acesso ao painel.",
            destructive: ! $ativo,
            onConfirm: 'usuarios::status-massa',
            params: ['ativo' => $ativo],
        );
    }

    #[On('usuarios::status-massa')]
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
            . '<x-shared.avatar :name="$nome" :src="$src" size="size-8" class="shrink-0" />'
            . '<span class="font-medium">{{ $nome }}</span>'
            . '</div>',
            ['nome' => $nome, 'src' => $u->urlAvatar()],
        );
    }

    protected function renderStatus(AdminUser $u): string
    {
        if ($u->estaAnonimizado()) {
            return Blade::render('<x-shared.badge variant="default" icon="tabler--user-off" size="sm">Anonimizado</x-shared.badge>');
        }

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
}
