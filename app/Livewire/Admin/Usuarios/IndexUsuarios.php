<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Admin\AtribuirPerfilEmMassaAction;
use App\Actions\Admin\BulkUserStatusAction;
use App\Actions\Admin\ToggleAdminUserStatusAction;
use App\DTOs\Admin\AtribuicaoPerfilMassaDTO;
use App\Exceptions\AccessException;
use App\Livewire\Concerns\WithDataTable;
use App\Models\AdminUser;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\HierarchyResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true])]
#[Title('Usuários admin')]
class IndexUsuarios extends Component
{
    use WithDataTable;

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'role', except: '')]
    public string $role = '';

    public string $perfilEmMassa = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AdminUser::class);

        if ($this->sort === '') {
            $this->sort = 'nome';
        }
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function alternarStatus(int $id, ToggleAdminUserStatusAction $action): void
    {
        $usuario = AdminUser::findOrFail($id);
        $this->authorize('toggleStatus', $usuario);

        try {
            $action->execute($usuario);
            session()->flash('toast.success', 'Status do usuário atualizado.');
        } catch (RuntimeException $e) {
            session()->flash('toast.error', $e->getMessage());
        } catch (AuthorizationException) {
            session()->flash('toast.error', 'Você não tem permissão para esta ação.');
        }
    }

    public function limparFiltros(): void
    {
        $this->reset(['busca', 'status', 'role']);
        $this->resetPage();
    }

    public function limparSelecao(): void
    {
        $this->reset(['selecionados', 'selecionarPagina', 'perfilEmMassa']);
    }

    public function atribuirPerfilEmMassa(AtribuirPerfilEmMassaAction $action): void
    {
        $this->authorize('create', AdminUser::class);

        if ($this->selecionados === [] || $this->perfilEmMassa === '') {
            session()->flash('toast.error', 'Selecione usuários e um perfil.');

            return;
        }

        try {
            $total = $action->execute(AtribuicaoPerfilMassaDTO::fromArray([
                'adminUserIds' => array_map('intval', $this->selecionados),
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

        if ($this->selecionados === []) {
            return;
        }

        try {
            $total = $action->execute(array_map('intval', $this->selecionados), $ativo, Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        $this->limparSelecao();
        session()->flash('toast.success', ($ativo ? 'Reativados' : 'Desativados') . " {$total} usuário(s).");
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function roles(): array
    {
        return Role::where('guard_name', 'admin')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * Perfis que o ator pode atribuir (abaixo da sua hierarquia).
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

    public function render(AdminUserService $service): View
    {
        $usuarios = $service->listarPaginado([
            'busca' => $this->busca,
            'status' => $this->status,
            'role' => $this->role,
            'sort' => $this->sort,
            'dir' => $this->dir,
        ]);

        return view('livewire.admin.usuarios.index-usuarios', [
            'usuarios' => $usuarios,
            'podeCriar' => Auth::guard('admin')->user()?->can('create', AdminUser::class) ?? false,
        ]);
    }

    /**
     * @return array<int, int>
     */
    protected function idsDaPaginaAtual(): array
    {
        $paginator = app(AdminUserService::class)->listarPaginado([
            'busca' => $this->busca,
            'status' => $this->status,
            'role' => $this->role,
            'sort' => $this->sort,
            'dir' => $this->dir,
        ]);

        return collect($paginator->items())
            ->map(static fn (AdminUser $usuario): int => (int) $usuario->getKey())
            ->all();
    }
}
