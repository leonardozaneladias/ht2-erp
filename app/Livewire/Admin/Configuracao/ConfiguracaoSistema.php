<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Configuracao;

use App\Enums\Admin\SettingsGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela de Configurações do Sistema (shell).
 *
 * Valida o acesso e monta a navegação por abas (Preline). Cada aba é um
 * componente Livewire isolado, com seu próprio estado, validação e gravação.
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Configurações')]
class ConfiguracaoSistema extends Component
{
    public function mount(): void
    {
        $user = auth('admin')->user();

        if ($user === null || ! $user->can('configuracoes.editar')) {
            throw new AuthorizationException('Acesso negado.');
        }
    }

    public function render(): View
    {
        $abas = SettingsGroup::abas();
        $valores = array_map(static fn (SettingsGroup $aba): string => $aba->value, $abas);
        $abaSolicitada = (string) request()->query('aba', 'general');

        return view('livewire.admin.configuracao.configuracao-sistema', [
            'abas' => $abas,
            'abaAtiva' => in_array($abaSolicitada, $valores, true) ? $abaSolicitada : 'general',
        ]);
    }
}
