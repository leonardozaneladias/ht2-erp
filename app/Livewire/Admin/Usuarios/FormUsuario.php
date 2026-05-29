<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Admin\CreateAdminUserAction;
use App\Actions\Admin\UpdateAdminUserAction;
use App\DTOs\Admin\AdminUserDTO;
use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('components.admin.layout', ['withLivewire' => true])]
class FormUsuario extends Component
{
    #[Locked]
    public ?int $usuarioId = null;

    public string $nome = '';

    public string $email = '';

    public string $password = '';

    public bool $ativo = true;

    /** @var array<int, string> */
    public array $roles = [];

    public function mount(?int $usuario = null): void
    {
        if ($usuario !== null) {
            $alvo = AdminUser::with('roles')->findOrFail($usuario);
            $this->authorize('update', $alvo);

            $this->usuarioId = $alvo->id;
            $this->nome = $alvo->nome;
            $this->email = $alvo->email;
            $this->ativo = (bool) $alvo->ativo;
            $this->roles = $alvo->getRoleNames()->all();

            return;
        }

        $this->authorize('create', AdminUser::class);
    }

    public function salvar(CreateAdminUserAction $criar, UpdateAdminUserAction $atualizar): void
    {
        $dados = $this->validate();
        $dto = AdminUserDTO::fromArray($dados);

        $alvo = $this->resolverUsuario();

        if ($alvo === null) {
            $criar->execute($dto);
            session()->flash('toast.success', 'Usuário admin criado.');
        } else {
            $atualizar->execute($alvo, $dto);
            session()->flash('toast.success', 'Usuário admin atualizado.');
        }

        $this->redirect(route('admin.usuarios.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.usuarios.form-usuario', [
            'rolesDisponiveis' => Role::where('guard_name', 'admin')->orderBy('name')->get(),
            'modo' => $this->usuarioId === null ? 'criar' : 'editar',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $senhaRegra = $this->usuarioId === null
            ? ['required', 'string', 'min:8', 'max:191']
            : ['nullable', 'string', 'min:8', 'max:191'];

        return [
            'nome' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('admin_users', 'email')->ignore($this->usuarioId)],
            'password' => $senhaRegra,
            'ativo' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'admin')],
        ];
    }

    protected function resolverUsuario(): ?AdminUser
    {
        return $this->usuarioId !== null ? AdminUser::findOrFail($this->usuarioId) : null;
    }
}
