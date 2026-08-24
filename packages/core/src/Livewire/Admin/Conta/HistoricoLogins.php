<?php

declare(strict_types=1);

namespace HT2ML\Core\Livewire\Admin\Conta;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * Aba "Segurança" — últimos acessos do próprio usuário (leitura).
 * Lazy: carrega após o conteúdo principal da aba, com skeleton.
 */
#[Lazy]
class HistoricoLogins extends Component
{
    public function placeholder(): View
    {
        return view('livewire.admin.placeholders.skeleton-table');
    }

    public function render(): View
    {
        $usuario = $this->usuario();

        return view('livewire.admin.conta.historico-logins', [
            'usuario' => $usuario,
            'registros' => $usuario->loginHistory()->latest('created_at')->limit(10)->get(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
