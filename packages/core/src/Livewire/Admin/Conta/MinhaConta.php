<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Conta;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Área "Minha conta" (shell). Monta a navegação por abas (Preline) e embute,
 * por aba, componentes Livewire isolados. Self-service: cada aba opera sobre o
 * próprio usuário autenticado (o middleware admin.auth garante a autenticação).
 */
#[Layout('components.admin.layout', ['withLivewire' => true, 'renderHeader' => false])]
#[Title('Minha conta')]
class MinhaConta extends Component
{
    public function render(): View
    {
        $abas = [
            ['value' => 'perfil', 'label' => 'Perfil', 'icon' => 'tabler--user'],
            ['value' => 'seguranca', 'label' => 'Segurança', 'icon' => 'tabler--shield-lock'],
            ['value' => 'preferencias', 'label' => 'Preferências', 'icon' => 'tabler--adjustments'],
        ];

        $valores = array_column($abas, 'value');
        $abaSolicitada = (string) request()->query('aba', 'perfil');

        return view('livewire.admin.conta.minha-conta', [
            'abas' => $abas,
            'abaAtiva' => in_array($abaSolicitada, $valores, true) ? $abaSolicitada : 'perfil',
        ]);
    }
}
