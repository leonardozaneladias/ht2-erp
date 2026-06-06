<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Aba "Segurança" — últimos acessos do próprio usuário (leitura).
 */
class HistoricoLogins extends Component
{
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
