<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use App\Actions\Admin\ConcederAcessoDiretoAction;
use App\Actions\Admin\RevogarAcessoDiretoAction;
use App\Actions\Admin\SyncRolesUsuarioAction;
use App\DTOs\Admin\AcessoEfetivoDTO;
use App\DTOs\Admin\ConcessaoAcessoDTO;
use App\DTOs\Admin\SyncRolesDTO;
use App\Enums\ModuloAcesso;
use App\Enums\TipoConcessao;
use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Models\PermissionGrant;
use App\Policies\AdminUserPolicy;
use App\Services\Admin\AcessoService;
use App\Services\Admin\HierarchyResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Painel de detalhe de uma pessoa dentro do hub de Controle de Acesso.
 * Abas: Perfis (com dirty-state), Acessos diretos (grant/deny) e Acesso efetivo (simulação).
 */
class PainelPessoa extends Component
{
    #[Locked]
    public ?int $usuarioId = null;

    public string $nome = '';

    public string $email = '';

    public bool $ativo = true;

    public string $aba = 'perfis';

    /** @var array<int, string> */
    public array $roles = [];

    /** Snapshot dos perfis salvos, para detectar alterações pendentes. */
    /** @var list<string> */
    public array $rolesOriginais = [];

    public bool $podeEditarPerfis = false;

    public bool $podeGerirAcessos = false;

    // Formulário de concessão direta (grant/deny).
    public bool $mostrarFormAcesso = false;

    public string $novoTipo = 'grant';

    public string $novaPermissao = '';

    public string $novaValidade = '';

    public string $novoMotivo = '';

    public function mount(int $usuarioId): void
    {
        $ator = Auth::guard('admin')->user();
        $usuario = AdminUser::with('roles')->findOrFail($usuarioId);
        $this->authorize('view', $usuario);

        $this->usuarioId = $usuario->id;
        $this->nome = $usuario->nome;
        $this->email = $usuario->email;
        $this->ativo = (bool) $usuario->ativo;
        $this->roles = $usuario->getRoleNames()->all();
        $this->rolesOriginais = $this->rolesOrdenadas();

        $policy = app(AdminUserPolicy::class);
        $this->podeEditarPerfis = $ator instanceof AdminUser && $policy->update($ator, $usuario);
        $this->podeGerirAcessos = $ator instanceof AdminUser && $policy->gerenciarAcessos($ator, $usuario);
    }

    // ── Aba Perfis ─────────────────────────────────────────────────────────

    public function salvarPerfis(SyncRolesUsuarioAction $action): void
    {
        if (! $this->podeEditarPerfis || $this->usuarioId === null) {
            return;
        }

        $gerenciaveis = $this->rolesGerenciaveis()->pluck('name')->all();

        $this->validate([
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in([...$gerenciaveis, ...$this->rolesOriginais])],
        ]);

        try {
            $action->execute(SyncRolesDTO::fromArray([
                'adminUserId' => $this->usuarioId,
                'roles' => array_values($this->roles),
            ]), Auth::guard('admin')->user());
        } catch (AccessException $e) {
            $this->dispatch('toast', variant: 'danger', message: $e->getMessage());

            return;
        }

        $this->rolesOriginais = $this->rolesOrdenadas();
        unset($this->perfisAlterados);

        $this->dispatch('toast', variant: 'success', message: 'Perfis atualizados.');
    }

    public function descartarPerfis(): void
    {
        $this->roles = $this->rolesOriginais;
        $this->resetValidation();
        unset($this->perfisAlterados);
    }

    /**
     * Quantidade de perfis adicionados/removidos ainda não salvos.
     */
    #[Computed]
    public function perfisAlterados(): int
    {
        $atual = $this->rolesOrdenadas();

        return count(array_diff($atual, $this->rolesOriginais))
            + count(array_diff($this->rolesOriginais, $atual));
    }

    /**
     * Perfis que o ator logado pode atribuir (abaixo do seu nível).
     *
     * @return Collection<int, Role>
     */
    #[Computed]
    public function rolesGerenciaveis(): Collection
    {
        $ator = Auth::guard('admin')->user();

        if (! $ator instanceof AdminUser) {
            return collect();
        }

        return app(HierarchyResolver::class)->rolesGerenciaveis($ator);
    }

    // ── Aba Acessos diretos ────────────────────────────────────────────────

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

    public function concederAcesso(ConcederAcessoDiretoAction $action): void
    {
        if (! $this->podeGerirAcessos || $this->usuarioId === null) {
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
            $this->dispatch('toast', variant: 'danger', message: $e->getMessage());

            return;
        }

        unset($this->acessosDiretos);
        $this->cancelarFormAcesso();
        $this->dispatch('toast', variant: 'success', message: 'Acesso registrado.');
    }

    public function revogarAcesso(int $grantId, RevogarAcessoDiretoAction $action): void
    {
        if (! $this->podeGerirAcessos || $this->usuarioId === null) {
            return;
        }

        $alvo = AdminUser::findOrFail($this->usuarioId);
        $this->authorize('gerenciarAcessos', $alvo);

        $grant = PermissionGrant::query()->where('admin_user_id', $this->usuarioId)->findOrFail($grantId);
        $action->execute($grant, 'Revogado pelo hub de controle de acesso.', Auth::guard('admin')->user());

        unset($this->acessosDiretos);
        $this->dispatch('toast', variant: 'success', message: 'Acesso revogado.');
    }

    /**
     * Concessões/negações diretas vigentes do usuário.
     *
     * @return Collection<int, PermissionGrant>
     */
    #[Computed]
    public function acessosDiretos(): Collection
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

    public function render(): View
    {
        $efetivo = $this->aba === 'efetivo' ? $this->acessoEfetivoColecao() : collect();
        $todos = $efetivo->flatten(1);

        return view('livewire.admin.acesso.painel-pessoa', [
            'acessoEfetivo' => $efetivo,
            'resumo' => [
                'permitidos' => $todos->filter(static fn (AcessoEfetivoDTO $dto): bool => $dto->permitido)->count(),
                'negados' => $todos->filter(static fn (AcessoEfetivoDTO $dto): bool => ! $dto->permitido)->count(),
                'total' => $todos->count(),
            ],
        ]);
    }

    // ── Aba Acesso efetivo ─────────────────────────────────────────────────

    /**
     * Acesso efetivo do usuário, explicado e agrupado por módulo.
     *
     * @return Collection<string, Collection<int, AcessoEfetivoDTO>>
     */
    private function acessoEfetivoColecao(): Collection
    {
        if ($this->usuarioId === null) {
            return collect();
        }

        $usuario = AdminUser::with('roles')->find($this->usuarioId);

        return $usuario !== null ? app(AcessoService::class)->simular($usuario) : collect();
    }

    /**
     * @return list<string>
     */
    private function rolesOrdenadas(): array
    {
        $roles = $this->roles;
        sort($roles);

        return $roles;
    }
}
