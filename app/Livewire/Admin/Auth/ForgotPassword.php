<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.admin.auth-layout')]
#[Title('Recuperar senha')]
final class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    public function sendLink(): void
    {
        $this->validate();

        $limite = app(LimiteTentativas::class);
        $chave = 'reset:' . Str::lower($this->email) . '|' . (request()->ip() ?? 'desconhecido');

        if ($limite->excedido($chave)) {
            $this->addError('email', 'Muitas solicitações. Tente novamente mais tarde.');

            return;
        }

        $limite->registrar($chave);

        $status = Password::broker('admins')->sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            app(AuditoriaSeguranca::class)->senhaResetSolicitada($this->email);
            session()->flash('status', __($status));
            $this->email = '';
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.auth.forgot-password');
    }
}
