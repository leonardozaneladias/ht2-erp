<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.admin.auth-layout')]
#[Title('Entrar')]
final class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('admin')->check()) {
            $this->redirect(route('admin.dashboard'), navigate: true);
        }
    }

    public function authenticate(): void
    {
        $this->validate();

        if (! Auth::guard('admin')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        session()->regenerate();

        $this->redirect(
            session()->pull('url.intended', route('admin.dashboard')),
            navigate: true,
        );
    }

    public function render(): View
    {
        return view('livewire.admin.auth.login');
    }
}
