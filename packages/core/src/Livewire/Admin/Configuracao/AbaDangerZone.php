<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Configuracao;

use HT2ML\Core\Actions\Admin\Lgpd\ExpurgarLogsAction;
use HT2ML\Core\Actions\Admin\Settings\ResetarAparenciaAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Zona de perigo: ações destrutivas/irreversíveis com reconfirmação por digitação.
 */
class AbaDangerZone extends Component
{
    public string $confirmacaoExpurgo = '';

    public string $confirmacaoReset = '';

    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('configuracoes.editar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function expurgarLogs(ExpurgarLogsAction $action): void
    {
        $this->authorize('configuracoes.editar');

        if ($this->confirmacaoExpurgo !== 'EXPURGAR') {
            $this->addError('confirmacaoExpurgo', 'Digite EXPURGAR para confirmar.');

            return;
        }

        $action->execute();
        $this->reset('confirmacaoExpurgo');
        session()->flash('success', 'Logs de auditoria antigos foram expurgados.');
    }

    public function resetarAparencia(ResetarAparenciaAction $action): void
    {
        $this->authorize('configuracoes.editar');

        if ($this->confirmacaoReset !== 'RESETAR') {
            $this->addError('confirmacaoReset', 'Digite RESETAR para confirmar.');

            return;
        }

        $action->execute();

        session()->flash('success', 'Identidade e aparência restauradas ao padrão.');

        // Recarrega para reaplicar marca/tema no chrome.
        $this->redirect(route('admin.configuracoes.index', ['aba' => 'appearance']));
    }

    public function render(): View
    {
        return view('livewire.admin.configuracao.aba-danger-zone');
    }
}
