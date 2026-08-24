<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Services\Admin\Security\ControleLockout;
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use Illuminate\Support\Facades\Auth;
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

        $chave = $this->chaveThrottle();
        $limite = app(LimiteTentativas::class);

        if ($limite->excedido($chave)) {
            app(AuditoriaSeguranca::class)->loginBloqueado($this->email);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => $limite->disponivelEm($chave)]),
            ]);
        }

        // Valida as credenciais SEM autenticar: com 2FA ativo, só autenticamos
        // após o desafio (estado intermediário "pendente"). Nunca antes.
        if (! Auth::guard('admin')->validate(['email' => $this->email, 'password' => $this->password])) {
            $limite->registrar($chave);
            app(ControleLockout::class)->registrarFalha($this->email);
            app(AuditoriaSeguranca::class)->loginFalhou($this->email);
            $this->addError('email', __('auth.failed'));

            return;
        }

        $usuario = AdminUser::where('email', $this->email)->first();

        if ($usuario !== null && app(ControleLockout::class)->estaBloqueada($usuario)) {
            $this->addError('email', 'Conta temporariamente bloqueada por excesso de tentativas. Tente novamente mais tarde.');

            return;
        }

        if ($usuario !== null && ! $usuario->ativo) {
            app(AuditoriaSeguranca::class)->loginFalhou($this->email);
            $this->addError('email', 'Esta conta está desativada.');

            return;
        }

        $limite->limpar($chave);

        if ($usuario !== null) {
            app(ControleLockout::class)->liberar($usuario);
        }

        if ($usuario !== null && $usuario->precisaSegundoFator()) {
            session()->put('2fa.pending', [
                'id' => $usuario->id,
                'remember' => $this->remember,
            ]);

            $this->redirect(route('admin.two-factor-challenge'), navigate: true);

            return;
        }

        Auth::guard('admin')->login($usuario, $this->remember);

        app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, false);

        if ($usuario->hasRole((string) config('access.super_admin_role', 'super-admin'))) {
            app(AlertaSeguranca::class)->superAdminLogou($usuario);
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

    private function chaveThrottle(): string
    {
        return 'login:' . Str::lower($this->email) . '|' . (request()->ip() ?? 'desconhecido');
    }
}
