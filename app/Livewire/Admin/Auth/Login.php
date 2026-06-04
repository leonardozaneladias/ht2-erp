<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

            return;
        }

        // Conveniência de desenvolvimento: pré-preenche o login do super-admin
        // semeado. Restrito ao ambiente local — nunca em produção.
        if (app()->isLocal()) {
            $this->email = 'admin@example.com';
            $this->password = 'password';
        }
    }

    public function authenticate(): void
    {
        $this->validate();

        // O login é um componente Livewire e não passa pelo middleware
        // `throttle`; o rate-limit é aplicado aqui (5 tentativas/min por e-mail+IP).
        $chave = $this->chaveThrottle();

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($chave)]),
            ]);
        }

        if (! Auth::guard('admin')->attempt(
            ['email' => $this->email, 'password' => $this->password],
            $this->remember,
        )) {
            RateLimiter::hit($chave, 60);
            $this->addError('email', __('auth.failed'));

            return;
        }

        RateLimiter::clear($chave);

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

    private function chaveThrottle(): string
    {
        return 'login:' . Str::lower($this->email) . '|' . (request()->ip() ?? 'desconhecido');
    }
}
