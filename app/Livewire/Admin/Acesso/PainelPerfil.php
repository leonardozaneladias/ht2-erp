<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use App\Actions\Admin\SalvarPerfilAction;
use App\DTOs\Admin\PerfilDTO;
use App\Exceptions\AccessException;
use App\Policies\RolePolicy;
use HT2ML\Core\Livewire\Concerns\EmiteNotificacoes;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Painel de detalhe de um perfil dentro do hub de Controle de Acesso.
 * Edição inline com dirty-state; perfis protegidos / fora da hierarquia ficam em leitura.
 */
class PainelPerfil extends Component
{
    use EmiteNotificacoes;

    #[Locked]
    public ?int $perfilId = null;

    public string $name = '';

    public int $nivel = 10;

    public string $descricao = '';

    /** @var array<int, string> */
    public array $permissions = [];

    public bool $podeEditar = true;

    public string $aba = 'permissoes';

    /** Snapshot do estado salvo, para detectar alterações pendentes. */
    /** @var array{name: string, nivel: int, descricao: string, permissions: list<string>} */
    public array $original = ['name' => '', 'nivel' => 0, 'descricao' => '', 'permissions' => []];

    public function mount(?int $perfilId = null): void
    {
        $user = Auth::guard('admin')->user();

        if ($perfilId === null) {
            $this->authorize('create', Role::class);
            $this->podeEditar = true;
            $this->original = $this->estadoAtual();

            return;
        }

        $role = Role::query()->where('guard_name', 'admin')->with('permissions')->findOrFail($perfilId);
        $this->authorize('view', $role);

        $this->perfilId = $role->id;
        $this->name = $role->name;
        $this->nivel = (int) $role->getAttribute('nivel');
        $this->descricao = (string) ($role->getAttribute('descricao') ?? '');
        $this->permissions = $role->permissions->pluck('name')->all();
        // Chama a Policy direto (sem Gate::before) para o bypass do super-admin
        // não mascarar a proteção de roles protegidas / fora da hierarquia.
        $this->podeEditar = $user instanceof AdminUser && app(RolePolicy::class)->update($user, $role);

        $this->original = $this->estadoAtual();
    }

    public function marcarModulo(string $modulo): void
    {
        if (! $this->podeEditar) {
            return;
        }

        $this->permissions = array_values(array_unique([...$this->permissions, ...$this->permissoesDoModulo($modulo)]));
    }

    public function limparModulo(string $modulo): void
    {
        if (! $this->podeEditar) {
            return;
        }

        $this->permissions = array_values(array_diff($this->permissions, $this->permissoesDoModulo($modulo)));
    }

    public function descartar(): void
    {
        $this->name = $this->original['name'];
        $this->nivel = $this->original['nivel'];
        $this->descricao = $this->original['descricao'];
        $this->permissions = $this->original['permissions'];
        $this->resetValidation();
        unset($this->alteracoes);
    }

    public function salvar(SalvarPerfilAction $action): void
    {
        if (! $this->podeEditar) {
            return;
        }

        $dados = $this->validate();

        try {
            $role = $action->execute(PerfilDTO::fromArray([
                'roleId' => $this->perfilId,
                'nome' => $dados['name'],
                'nivel' => $dados['nivel'],
                'descricao' => $this->descricao !== '' ? $this->descricao : null,
                'permissoes' => array_values($this->permissions),
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            $this->notificarErro($e->getMessage());

            return;
        }

        $this->perfilId = $role->id;
        $this->name = $role->name;
        $this->original = $this->estadoAtual();
        unset($this->alteracoes);

        $this->notificarSucesso('Perfil salvo.');
        $this->dispatch('perfil-salvo', id: $role->id);
    }

    /**
     * Quantidade de alterações pendentes (campos + permissões adicionadas/removidas).
     */
    #[Computed]
    public function alteracoes(): int
    {
        $atual = $this->estadoAtual();

        $total = 0;

        foreach (['name', 'nivel', 'descricao'] as $campo) {
            if ($atual[$campo] !== $this->original[$campo]) {
                $total++;
            }
        }

        $total += count(array_diff($atual['permissions'], $this->original['permissions']));
        $total += count(array_diff($this->original['permissions'], $atual['permissions']));

        return $total;
    }

    /**
     * Usuários que possuem este perfil.
     *
     * @return EloquentCollection<int, AdminUser>
     */
    #[Computed]
    public function membros(): EloquentCollection
    {
        if ($this->perfilId === null) {
            return new EloquentCollection;
        }

        $role = Role::query()->where('guard_name', 'admin')->find($this->perfilId);

        if ($role === null) {
            return new EloquentCollection;
        }

        /** @var EloquentCollection<int, AdminUser> $usuarios */
        $usuarios = $role->users()->orderBy('nome')->get();

        return $usuarios;
    }

    public function render(): View
    {
        $permissoesAgrupadas = Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('label')
            ->get()
            ->groupBy(fn (Permission $permissao): string => (string) ($permissao->getAttribute('modulo') ?? 'outros'));

        return view('livewire.admin.acesso.painel-perfil', [
            'permissoesAgrupadas' => $permissoesAgrupadas,
            'modo' => $this->perfilId === null ? 'criar' : 'editar',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:80',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', 'admin'))
                    ->ignore($this->perfilId),
            ],
            'nivel' => ['required', 'integer', 'min:1', 'max:999'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'admin')],
        ];
    }

    /**
     * @return array{name: string, nivel: int, descricao: string, permissions: list<string>}
     */
    private function estadoAtual(): array
    {
        $permissions = $this->permissions;
        sort($permissions);

        return [
            'name' => $this->name,
            'nivel' => $this->nivel,
            'descricao' => $this->descricao,
            'permissions' => $permissions,
        ];
    }

    /**
     * @return list<string>
     */
    private function permissoesDoModulo(string $modulo): array
    {
        /** @var list<string> $nomes */
        $nomes = Permission::query()
            ->where('guard_name', 'admin')
            ->where('modulo', $modulo)
            ->pluck('name')
            ->all();

        return $nomes;
    }
}
