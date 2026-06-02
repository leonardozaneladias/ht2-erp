<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use App\Actions\Admin\SyncPermissoesPerfilAction;
use App\DTOs\Admin\SyncPermissoesPerfilDTO;
use App\Exceptions\AccessException;
use App\Services\Admin\AcessoService;
use App\Services\Admin\HierarchyResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Matriz de permissões')]
class MatrizPermissoes extends Component
{
    /**
     * Estado da grade: [roleId => [permissionId => bool]].
     * Indexado por ID (não por nome) porque nomes têm ponto e quebram o wire:model aninhado.
     *
     * @var array<int, array<int, bool>>
     */
    public array $grade = [];

    public function mount(): void
    {
        $user = Auth::guard('admin')->user();

        if ($user === null || ! $user->can('acessos.matriz')) {
            throw new AuthorizationException('Acesso negado.');
        }

        foreach ($this->perfisEditaveis() as $role) {
            $marcadas = $role->permissions->pluck('id')->all();
            $this->grade[$role->id] = [];

            foreach (Permission::where('guard_name', 'admin')->pluck('id') as $permissionId) {
                $this->grade[$role->id][(int) $permissionId] = in_array($permissionId, $marcadas, true);
            }
        }
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function marcarModulo(int $roleId, array $permissionIds, bool $marcar): void
    {
        foreach ($permissionIds as $permissionId) {
            $this->grade[$roleId][$permissionId] = $marcar;
        }
    }

    public function salvarPerfil(int $roleId, SyncPermissoesPerfilAction $action): void
    {
        $idsMarcados = collect($this->grade[$roleId] ?? [])
            ->filter(static fn (bool $marcado): bool => $marcado)
            ->keys()
            ->all();

        $permissoes = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('id', $idsMarcados)
            ->pluck('name')
            ->all();

        try {
            $action->execute(SyncPermissoesPerfilDTO::fromArray([
                'roleId' => $roleId,
                'permissoes' => $permissoes,
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        session()->flash('toast.success', 'Permissões do perfil atualizadas.');
    }

    public function render(AcessoService $service): View
    {
        return view('livewire.admin.acesso.matriz-permissoes', [
            'perfis' => $this->perfisEditaveis(),
            'superAdmin' => $this->superAdmin(),
            'permissoesPorModulo' => $service->permissoesPorModulo(),
        ]);
    }

    /**
     * @return EloquentCollection<int, Role>
     */
    protected function perfisEditaveis(): EloquentCollection
    {
        $user = Auth::guard('admin')->user();

        $query = Role::query()
            ->where('guard_name', 'admin')
            ->where('name', '!=', $this->superAdminRole())
            ->with('permissions')
            ->orderByDesc('nivel')
            ->orderBy('name');

        // Não-super-admin só edita perfis de nível estritamente abaixo do seu.
        if ($user !== null && ! $user->hasRole($this->superAdminRole())) {
            $query->where('nivel', '<', app(HierarchyResolver::class)->nivelDe($user));
        }

        return $query->get();
    }

    protected function superAdmin(): ?Role
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->where('name', $this->superAdminRole())
            ->first();
    }

    private function superAdminRole(): string
    {
        return (string) config('access.super_admin_role', 'super-admin');
    }
}
