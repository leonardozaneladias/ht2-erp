<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Acesso;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Hub unificado de controle de acesso: master-detail com duas lentes (Perfis e Pessoas).
 * O shell cuida da navegação (lente, busca, seleção, histórico); os detalhes ficam nos painéis filhos.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
class ControleAcesso extends Component
{
    /** Lente ativa da lista lateral: 'perfis' ou 'pessoas'. */
    #[Url(as: 'lente')]
    public string $lente = 'perfis';

    /** ID do item selecionado na lente ativa (role ou admin user). */
    #[Url(as: 'id')]
    public ?int $selecionadoId = null;

    /** Quando true, o painel de perfil abre em modo de criação. */
    public bool $modoNovo = false;

    /** Quando true, o painel mostra o histórico de acesso (transversal). */
    public bool $mostrarHistorico = false;

    public string $busca = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);

        if (! $this->lenteValida($this->lente) || ($this->lente === 'pessoas' && ! $this->podeVerPessoas())) {
            $this->lente = 'perfis';
        }
    }

    public function trocarLente(string $lente): void
    {
        if (! $this->lenteValida($lente) || ($lente === 'pessoas' && ! $this->podeVerPessoas())) {
            return;
        }

        $this->lente = $lente;
        $this->selecionadoId = null;
        $this->modoNovo = false;
        $this->mostrarHistorico = false;
        $this->busca = '';
    }

    public function selecionar(int $id): void
    {
        $this->modoNovo = false;
        $this->mostrarHistorico = false;
        $this->selecionadoId = $id;
    }

    public function novoPerfil(): void
    {
        $this->authorize('create', Role::class);

        $this->lente = 'perfis';
        $this->selecionadoId = null;
        $this->mostrarHistorico = false;
        $this->modoNovo = true;
    }

    public function verHistorico(): void
    {
        if (! $this->podeVerHistorico()) {
            return;
        }

        $this->mostrarHistorico = true;
        $this->selecionadoId = null;
        $this->modoNovo = false;
    }

    #[On('perfil-salvo')]
    public function aoSalvarPerfil(int $id): void
    {
        $this->modoNovo = false;
        $this->mostrarHistorico = false;
        $this->selecionadoId = $id;
    }

    public function render(): View
    {
        $user = Auth::guard('admin')->user();

        return view('livewire.admin.acesso.controle-acesso', [
            'itens' => $this->lente === 'pessoas' ? $this->pessoas() : $this->perfis(),
            'podeCriarPerfil' => $user instanceof AdminUser && $user->can('create', Role::class),
            'podeVerPessoas' => $this->podeVerPessoas(),
            'podeVerHistorico' => $this->podeVerHistorico(),
        ])->title('Controle de acesso');
    }

    private function lenteValida(string $lente): bool
    {
        return in_array($lente, ['perfis', 'pessoas'], true);
    }

    private function podeVerPessoas(): bool
    {
        $user = Auth::guard('admin')->user();

        return $user instanceof AdminUser && $user->can('usuarios.listar');
    }

    private function podeVerHistorico(): bool
    {
        $user = Auth::guard('admin')->user();

        return $user instanceof AdminUser && $user->can('acessos.historico');
    }

    /**
     * @return Collection<int, Role>
     */
    private function perfis(): Collection
    {
        return Role::query()
            ->where('guard_name', 'admin')
            ->when($this->busca !== '', fn ($query) => $query->where('name', 'like', "%{$this->busca}%"))
            ->orderByDesc('nivel')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, AdminUser>
     */
    private function pessoas(): Collection
    {
        return AdminUser::query()
            ->when($this->busca !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('nome', 'like', "%{$this->busca}%")
                    ->orWhere('email', 'like', "%{$this->busca}%"),
            ))
            ->orderBy('nome')
            ->get();
    }
}
