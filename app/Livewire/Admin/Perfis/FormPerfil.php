<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Perfis;

use App\Actions\Admin\SalvarPerfilAction;
use App\DTOs\Admin\PerfilDTO;
use App\Exceptions\AccessException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true])]
class FormPerfil extends Component
{
    #[Locked]
    public ?int $perfilId = null;

    public string $name = '';

    public int $nivel = 10;

    public string $descricao = '';

    /** @var array<int, string> */
    public array $permissions = [];

    public function mount(?int $perfil = null): void
    {
        if ($perfil !== null) {
            $alvo = Role::where('guard_name', 'admin')->findOrFail($perfil);
            $this->authorize('update', $alvo);
            $this->perfilId = $alvo->id;
            $this->name = $alvo->name;
            $this->nivel = (int) $alvo->getAttribute('nivel');
            $this->descricao = (string) ($alvo->getAttribute('descricao') ?? '');
            $this->permissions = $alvo->permissions->pluck('name')->all();

            return;
        }

        $this->authorize('create', Role::class);
    }

    public function marcarModulo(string $modulo): void
    {
        $this->permissions = array_values(array_unique([...$this->permissions, ...$this->permissoesDoModulo($modulo)]));
    }

    public function limparModulo(string $modulo): void
    {
        $this->permissions = array_values(array_diff($this->permissions, $this->permissoesDoModulo($modulo)));
    }

    public function salvar(SalvarPerfilAction $action): void
    {
        $dados = $this->validate();

        try {
            $action->execute(PerfilDTO::fromArray([
                'roleId' => $this->perfilId,
                'nome' => $dados['name'],
                'nivel' => $dados['nivel'],
                'descricao' => $this->descricao !== '' ? $this->descricao : null,
                'permissoes' => array_values($this->permissions),
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        session()->flash('toast.success', $this->perfilId === null ? 'Perfil criado.' : 'Perfil atualizado.');
        $this->redirect(route('admin.perfis.index'), navigate: true);
    }

    public function render(): View
    {
        $permissoesAgrupadas = Permission::where('guard_name', 'admin')
            ->orderBy('label')
            ->get()
            ->groupBy(fn (Permission $permissao): string => (string) ($permissao->getAttribute('modulo') ?? 'outros'));

        return view('livewire.admin.perfis.form-perfil', [
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
     * @return list<string>
     */
    private function permissoesDoModulo(string $modulo): array
    {
        /** @var list<string> $nomes */
        $nomes = Permission::where('guard_name', 'admin')
            ->where('modulo', $modulo)
            ->pluck('name')
            ->all();

        return $nomes;
    }
}
