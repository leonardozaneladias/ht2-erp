<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Usuarios;

use App\Actions\Admin\ConcederAcessoDiretoAction;
use App\Actions\Admin\CreateAdminUserAction;
use App\Actions\Admin\RevogarAcessoDiretoAction;
use App\Actions\Admin\SyncAcessoEmpresaAction;
use App\Actions\Admin\UpdateAdminUserAction;
use App\DTOs\Admin\AdminUserDTO;
use App\DTOs\Admin\ConcessaoAcessoDTO;
use App\Enums\ModuloAcesso;
use App\Enums\TipoConcessao;
use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Models\Empresa;
use App\Models\PermissionGrant;
use App\Services\Admin\HierarchyResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class FormUsuario extends Component
{
    #[Locked]
    public ?int $usuarioId = null;

    public string $nome = '';

    public string $email = '';

    public string $telefone = '';

    public string $cargo = '';

    public string $password = '';

    // Como o novo usuário recebe a senha: convite por e-mail (padrão) ou manual.
    public string $modoAcesso = 'convite';

    public bool $ativo = true;

    /** @var array<int, string> */
    public array $roles = [];

    // Empresas a que o usuário tem acesso (todas as filiais).
    /** @var array<int, int> */
    public array $empresasAcesso = [];

    // Painel de concessão de acesso extra (grant/deny direto).
    public bool $mostrarFormAcesso = false;

    public string $novoTipo = 'grant';

    public string $novaPermissao = '';

    public string $novaValidade = '';

    public string $novoMotivo = '';

    public function mount(?int $usuario = null): void
    {
        if ($usuario !== null) {
            $alvo = AdminUser::with('roles')->findOrFail($usuario);
            $this->authorize('update', $alvo);
            $this->usuarioId = $alvo->id;
            $this->nome = $alvo->nome;
            $this->email = $alvo->email;
            $this->telefone = (string) $alvo->telefone;
            $this->cargo = (string) $alvo->cargo;
            $this->ativo = (bool) $alvo->ativo;
            $this->roles = $alvo->getRoleNames()->all();
            $this->empresasAcesso = $alvo->empresasAcessiveis()->pluck('empresas.id')->all();

            return;
        }

        $this->authorize('create', AdminUser::class);
    }

    public function salvar(
        CreateAdminUserAction $criar,
        UpdateAdminUserAction $atualizar,
        \App\Actions\Admin\Convites\ConvidarUsuarioAction $convidar,
    ): void {
        $dados = $this->validate();
        $alvo = $this->resolverUsuario();
        $porConvite = $alvo === null && $this->modoAcesso === 'convite';

        if ($porConvite) {
            // Senha provisória aleatória, inutilizável: o convidado define a sua no aceite.
            $dados['password'] = \Illuminate\Support\Str::password(40);
        }

        $dto = AdminUserDTO::fromArray($dados);

        if ($alvo === null) {
            $usuario = $criar->execute($dto);

            if ($porConvite) {
                $convidar->execute($usuario, Auth::guard('admin')->user());
                session()->flash('toast.success', 'Usuário criado. Convite enviado por e-mail.');
            } else {
                session()->flash('toast.success', 'Usuário admin criado.');
            }
        } else {
            $atualizar->execute($alvo, $dto);
            session()->flash('toast.success', 'Usuário admin atualizado.');
        }

        $this->redirect(route('admin.usuarios.index'), navigate: true);
    }

    public function reenviarConvite(\App\Actions\Admin\Convites\ConvidarUsuarioAction $convidar): void
    {
        $alvo = $this->resolverUsuario();

        if ($alvo === null) {
            return;
        }

        $this->authorize('update', $alvo);
        $convidar->execute($alvo, Auth::guard('admin')->user());

        session()->flash('toast.success', 'Convite reenviado por e-mail.');
    }

    #[Computed]
    public function emailNaoVerificado(): bool
    {
        if ($this->usuarioId === null) {
            return false;
        }

        return AdminUser::findOrFail($this->usuarioId)->email_verified_at === null;
    }

    public function salvarEmpresas(SyncAcessoEmpresaAction $action): void
    {
        $this->authorize('empresas.acessos');

        if ($this->usuarioId === null) {
            return;
        }

        $action->execute(
            AdminUser::findOrFail($this->usuarioId),
            array_map('intval', $this->empresasAcesso),
        );

        session()->flash('toast.success', 'Acesso a empresas atualizado.');
    }

    /**
     * Empresas ativas que podem ser concedidas ao usuário.
     *
     * @return Collection<int, Empresa>
     */
    #[Computed]
    public function empresasDisponiveis(): Collection
    {
        return Empresa::query()->ativas()->orderBy('nome')->get();
    }

    #[Computed]
    public function podeGerirEmpresas(): bool
    {
        return Auth::guard('admin')->user()?->can('empresas.acessos') ?? false;
    }

    public function abrirFormAcesso(): void
    {
        $this->reset(['novoTipo', 'novaPermissao', 'novaValidade', 'novoMotivo']);
        $this->resetValidation();
        $this->mostrarFormAcesso = true;
    }

    public function cancelarFormAcesso(): void
    {
        $this->mostrarFormAcesso = false;
        $this->reset(['novoTipo', 'novaPermissao', 'novaValidade', 'novoMotivo']);
        $this->resetValidation();
    }

    public function concederAcessoExtra(ConcederAcessoDiretoAction $action): void
    {
        if ($this->usuarioId === null) {
            return;
        }

        $alvo = AdminUser::findOrFail($this->usuarioId);
        $this->authorize('gerenciarAcessos', $alvo);

        $dados = $this->validate([
            'novoTipo' => ['required', Rule::enum(TipoConcessao::class)],
            'novaPermissao' => ['required', 'string', Rule::exists('permissions', 'name')->where('guard_name', 'admin')],
            'novaValidade' => ['nullable', 'date', 'after:now'],
            'novoMotivo' => ['required', 'string', 'min:3', 'max:255'],
        ], attributes: [
            'novoTipo' => 'tipo',
            'novaPermissao' => 'permissão',
            'novaValidade' => 'validade',
            'novoMotivo' => 'motivo',
        ]);

        try {
            $action->execute(ConcessaoAcessoDTO::fromArray([
                'adminUserId' => $this->usuarioId,
                'permissao' => $dados['novaPermissao'],
                'tipo' => TipoConcessao::from($dados['novoTipo']),
                'motivo' => $dados['novoMotivo'],
                'expiraEm' => $dados['novaValidade'] !== '' ? $dados['novaValidade'] : null,
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            session()->flash('toast.error', $e->getMessage());

            return;
        }

        unset($this->acessosExtras);
        $this->cancelarFormAcesso();
        session()->flash('toast.success', 'Acesso registrado.');
    }

    public function solicitarRevogarAcessoExtra(int $grantId): void
    {
        $this->dispatch(
            'confirm',
            title: 'Revogar este acesso?',
            text: 'A concessão direta deixa de valer imediatamente.',
            destructive: true,
            onConfirm: 'form-usuario::revogar-acesso',
            params: ['grantId' => $grantId],
        );
    }

    #[\Livewire\Attributes\On('form-usuario::revogar-acesso')]
    public function revogarAcessoExtra(int $grantId, RevogarAcessoDiretoAction $action): void
    {
        if ($this->usuarioId === null) {
            return;
        }

        $alvo = AdminUser::findOrFail($this->usuarioId);
        $this->authorize('gerenciarAcessos', $alvo);

        $grant = PermissionGrant::where('admin_user_id', $this->usuarioId)->findOrFail($grantId);
        $action->execute($grant, 'Revogado pela tela de edição do usuário.', Auth::guard('admin')->user());

        unset($this->acessosExtras);
        session()->flash('toast.success', 'Acesso revogado.');
    }

    /**
     * Concessões/negações diretas vigentes do usuário.
     *
     * @return Collection<int, PermissionGrant>
     */
    #[Computed]
    public function acessosExtras(): Collection
    {
        if ($this->usuarioId === null) {
            return collect();
        }

        return PermissionGrant::query()
            ->where('admin_user_id', $this->usuarioId)
            ->vigentes()
            ->with('permission')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Permissões do catálogo para o seletor (rotuladas por módulo).
     *
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function permissoesParaSelect(): array
    {
        return Permission::query()
            ->where('guard_name', 'admin')
            ->orderBy('modulo')
            ->orderBy('label')
            ->get()
            ->map(static function (Permission $permissao): array {
                $modulo = $permissao->getAttribute('modulo');
                $moduloLabel = is_string($modulo) ? (ModuloAcesso::tryFrom($modulo)?->label() ?? $modulo) : null;
                $label = $permissao->getAttribute('label') ?? $permissao->name;

                return [
                    'value' => $permissao->name,
                    'label' => $moduloLabel !== null ? "[{$moduloLabel}] {$label}" : $label,
                ];
            })
            ->all();
    }

    public function render(HierarchyResolver $hierarchy): View
    {
        $ator = Auth::guard('admin')->user();
        $rolesDisponiveis = $ator instanceof AdminUser
            ? $hierarchy->rolesGerenciaveis($ator)
            : collect();

        return view('livewire.admin.usuarios.form-usuario', [
            'rolesDisponiveis' => $rolesDisponiveis,
            'modo' => $this->usuarioId === null ? 'criar' : 'editar',
        ])->title($this->usuarioId === null ? 'Novo usuário admin' : 'Editar usuário admin');
    }

    protected function resolverUsuario(): ?AdminUser
    {
        return $this->usuarioId !== null ? AdminUser::find($this->usuarioId) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $senhaRegra = match (true) {
            // Criação por convite: a senha é definida pelo convidado no aceite.
            $this->usuarioId === null && $this->modoAcesso === 'convite' => ['exclude'],
            $this->usuarioId === null => ['required', 'string', \App\Support\Settings\PasswordPolicy::rule(), 'max:191'],
            default => ['nullable', 'string', \App\Support\Settings\PasswordPolicy::rule(), 'max:191'],
        };

        $ator = Auth::guard('admin')->user();
        $gerenciaveis = $ator instanceof AdminUser
            ? app(HierarchyResolver::class)->rolesGerenciaveis($ator)->pluck('name')->all()
            : [];

        return [
            'nome' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', Rule::unique('admin_users', 'email')->ignore($this->usuarioId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'password' => $senhaRegra,
            'ativo' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in($gerenciaveis)],
        ];
    }
}
