<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Perfis;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
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

    /** @var array<int, string> */
    public array $permissions = [];

    public function mount(?int $perfil = null): void
    {
        if ($perfil !== null) {
            $alvo = Role::where('guard_name', 'admin')->findOrFail($perfil);
            $this->authorize('update', $alvo);
            $this->perfilId = $alvo->id;
            $this->name = $alvo->name;
            $this->permissions = $alvo->permissions->pluck('name')->all();

            return;
        }

        $this->authorize('create', Role::class);
    }

    public function salvar(): void
    {
        $dados = $this->validate();

        $alvo = $this->perfilId !== null
            ? Role::where('guard_name', 'admin')->findOrFail($this->perfilId)
            : Role::create(['name' => $dados['name'], 'guard_name' => 'admin']);

        if ($this->perfilId !== null) {
            $alvo->update(['name' => $dados['name']]);
        }

        $alvo->syncPermissions($dados['permissions']);

        /** @var Model $perfilModel */
        $perfilModel = $alvo;
        activity('roles')
            ->performedOn($perfilModel)
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties(['permissions' => $dados['permissions']])
            ->event($this->perfilId === null ? 'created' : 'updated')
            ->log($this->perfilId === null ? 'Perfil criado' : 'Perfil atualizado');

        session()->flash(
            'toast.success',
            $this->perfilId === null ? 'Perfil criado.' : 'Perfil atualizado.',
        );

        $this->redirect(route('admin.perfis.index'), navigate: true);
    }

    public function render(): View
    {
        $permissoesAgrupadas = Permission::where('guard_name', 'admin')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => explode('.', $p->name, 2)[0])
            ->sortKeys();

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
                    ->where(fn ($q) => $q->where('guard_name', 'admin'))
                    ->ignore($this->perfilId),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'admin')],
        ];
    }
}
