<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Admin\ToggleAdminUserStatusAction;
use App\Models\AdminUser;
use App\Services\Admin\AdminUserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true])]
#[Title('Usuários admin')]
class IndexUsuarios extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $busca = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    #[Url(as: 'role', except: '')]
    public string $role = '';

    public string $sort = 'nome';

    public string $dir = 'asc';

    public function mount(): void
    {
        $this->authorize('viewAny', AdminUser::class);
    }

    public function updatingBusca(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function ordenarPor(string $coluna): void
    {
        if ($this->sort === $coluna) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sort = $coluna;
        $this->dir = 'asc';
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
}
