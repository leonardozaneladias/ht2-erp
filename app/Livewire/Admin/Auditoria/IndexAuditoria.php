<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auditoria;

use App\Actions\Admin\Lgpd\ExpurgarLogsAction;
use App\Livewire\Concerns\EmiteNotificacoes;
use App\Models\Activity;
use App\Models\AdminUser;
use App\Support\Audit\FormatadorDiff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela de logs de auditoria (append-only).
 *
 * Wrapper fino: valida o acesso e embute o grid PowerGrid
 * (App\Livewire\Admin\Auditoria\AuditoriaTable).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Logs de auditoria')]
class IndexAuditoria extends Component
{
    use EmiteNotificacoes;

    // Atividade aberta no drawer de detalhes (null = drawer sem conteúdo).
    #[Locked]
    public ?int $detalheId = null;

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('auditoria.visualizar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    /**
     * Confirmação destrutiva temática (bridge SweetAlert2) antes do expurgo.
     */
    public function solicitarExpurgo(): void
    {
        $this->dispatch(
            'confirm',
            title: 'Expurgar logs antigos?',
            text: 'Remove os logs de auditoria além do teto de retenção. Esta ação não pode ser desfeita.',
            destructive: true,
            onConfirm: 'auditoria::expurgar',
        );
    }

    #[On('auditoria::expurgar')]
    public function expurgar(ExpurgarLogsAction $action): void
    {
        $user = auth('admin')->user();

        if (! $user instanceof AdminUser || ! $user->hasRole((string) config('access.super_admin_role', 'super-admin'))) {
            throw new AuthorizationException('Acesso negado.');
        }

        $action->execute();
        $this->notificarSucesso('Logs antigos expurgados.');
    }

    /**
     * Abre o drawer de detalhes de uma atividade. O scope `visiveisPara` é
     * reaplicado aqui — sem ele o detalhe vazaria registros de outra empresa.
     */
    #[On('auditoria::detalhar')]
    public function detalhar(int $id): void
    {
        $user = auth('admin')->user();

        if (! $user instanceof AdminUser || ! $user->can('auditoria.visualizar')) {
            throw new AuthorizationException('Acesso negado.');
        }

        $existe = Activity::query()->visiveisPara($user)->whereKey($id)->exists();

        if (! $existe) {
            $this->notificarErro('Registro de auditoria não encontrado.');

            return;
        }

        $this->detalheId = $id;
        unset($this->detalhe, $this->mudancas);
        $this->dispatch('auditoria-abrir-detalhe');
    }

    #[Computed]
    public function detalhe(): ?Activity
    {
        $user = auth('admin')->user();

        if ($this->detalheId === null || ! $user instanceof AdminUser) {
            return null;
        }

        return Activity::query()
            ->visiveisPara($user)
            ->with(['causer', 'empresa', 'filial'])
            ->find($this->detalheId);
    }

    /**
     * Diff campo → (antes, depois) lido de attribute_changes, já formatado
     * para exibição (created só tem "depois"; deleted só tem "antes").
     *
     * @return list<array{campo: string, antes: ?string, depois: ?string}>
     */
    #[Computed]
    public function mudancas(): array
    {
        return FormatadorDiff::linhas($this->detalhe());
    }

    #[Computed]
    public function podeExpurgar(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof AdminUser && $user->hasRole((string) config('access.super_admin_role', 'super-admin'));
    }

    public function render(): View
    {
        return view('livewire.admin.auditoria.index-auditoria');
    }
}
