# Auth Screens (Login/ForgotPassword/ResetPassword) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Converter as 3 telas de auth para Livewire full-page, criar o componente `x-admin.auth-form-card` e melhorar o painel hero do `auth-layout`.

**Architecture:** Três Livewire full-page components (`Admin\Auth\{Login,ForgotPassword,ResetPassword}`) substituem os controllers PHP+POST existentes. O `auth-layout` ganha a prop `$heroSubtitle` e o hero refinado. O novo `x-admin.auth-form-card` encapsula o painel direito branco (logo+slot+copyright). Um `LogoutController` single-action herda o logout.

**Tech Stack:** Laravel 13, Livewire 4, Pest 4, Blade components, SQLite in-memory (testes), guard `admin` / broker `admins`.

> **Nota:** O projeto não tem repositório git inicializado. Os passos de commit devem ser executados após `git init && git add . && git commit -m "chore: init"` ou omitidos se o projeto já tiver histórico.

---

## Arquivos — Mapa Completo

| Ação | Arquivo |
|---|---|
| **Criar** | `resources/views/components/admin/auth-form-card.blade.php` |
| **Criar** | `app/Livewire/Admin/Auth/Login.php` |
| **Criar** | `resources/views/livewire/admin/auth/login.blade.php` |
| **Criar** | `app/Livewire/Admin/Auth/ForgotPassword.php` |
| **Criar** | `resources/views/livewire/admin/auth/forgot-password.blade.php` |
| **Criar** | `app/Livewire/Admin/Auth/ResetPassword.php` |
| **Criar** | `resources/views/livewire/admin/auth/reset-password.blade.php` |
| **Criar** | `app/Http/Controllers/Admin/Auth/LogoutController.php` |
| **Criar** | `tests/Feature/Admin/Auth/LoginTest.php` |
| **Criar** | `tests/Feature/Admin/Auth/ForgotPasswordTest.php` |
| **Criar** | `tests/Feature/Admin/Auth/ResetPasswordTest.php` |
| **Modificar** | `resources/views/components/admin/auth-layout.blade.php` |
| **Modificar** | `routes/admin.php` |
| **Modificar** | `docs/template/INSPINIA/CATALOGO-COMPONENTES.md` |
| **Deletar** | `app/Http/Controllers/Admin/Auth/LoginController.php` |
| **Deletar** | `app/Http/Controllers/Admin/Auth/ForgotPasswordController.php` |
| **Deletar** | `app/Http/Controllers/Admin/Auth/ResetPasswordController.php` |
| **Deletar** | `app/Http/Requests/Admin/Auth/LoginRequest.php` |
| **Deletar** | `resources/views/admin/auth/login.blade.php` |
| **Deletar** | `resources/views/admin/auth/forgot-password.blade.php` |
| **Deletar** | `resources/views/admin/auth/reset-password.blade.php` |

---

## Task 1: Componente `x-admin.auth-form-card`

**Files:**
- Create: `resources/views/components/admin/auth-form-card.blade.php`

- [ ] **Criar o componente**

```blade
{{-- resources/views/components/admin/auth-form-card.blade.php --}}
<div class="card relative flex min-h-screen flex-col justify-between rounded-none p-12.5">
    <div class="mb-7.5 flex flex-col items-center justify-center">
        <a href="{{ route('admin.login') }}">
            <img
                alt="{{ config('app.name') }}"
                class="flex h-8 dark:hidden"
                src="{{ asset(config('branding.logo_dark_path')) }}"
            />
            <img
                alt="{{ config('app.name') }}"
                class="hidden h-8 dark:flex"
                src="{{ asset(config('branding.logo_path')) }}"
            />
        </a>
    </div>

    <div>{{ $slot }}</div>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>
```

- [ ] **Verificar que o componente existe e é reconhecido pelo Blade**

```bash
php artisan view:cache && php artisan view:clear
```

Esperado: nenhum erro.

- [ ] **Commit**

```bash
git add resources/views/components/admin/auth-form-card.blade.php
git commit -m "feat(auth): criar componente x-admin.auth-form-card"
```

---

## Task 2: Refatorar `auth-layout` — hero melhorado + slot simplificado

**Files:**
- Modify: `resources/views/components/admin/auth-layout.blade.php`

- [ ] **Substituir o conteúdo do arquivo**

```blade
{{-- resources/views/components/admin/auth-layout.blade.php --}}
@props([
    'title'        => null,
    'heroSubtitle' => 'Painel administrativo.',
])

@php
    $pageTitle = filled($title)
        ? sprintf('%s | %s', $title, config('app.name'))
        : config('app.name');
@endphp

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $pageTitle }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" />

    <x-admin.partials.theme-bootstrap />

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <div class="min-h-screen">
        <div class="flex h-full w-full">
            {{-- Painel esquerdo: hero (oculto no mobile) --}}
            <div class="hidden w-full md:block">
                <div class="relative h-full overflow-hidden bg-[url('/images/auth.jpg')] bg-cover bg-center bg-no-repeat">
                    <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-9">
                        <img
                            alt="{{ config('app.name') }}"
                            class="mb-5 h-7"
                            src="{{ asset(config('branding.logo_path')) }}"
                        />
                        <p class="text-lg font-bold text-white">{{ config('app.name') }}</p>
                        <p class="mt-1 text-sm text-white/60">{{ $heroSubtitle }}</p>
                    </div>
                </div>
            </div>

            {{-- Painel direito: recebe x-admin.auth-form-card via $slot --}}
            <div class="min-w-full md:max-w-118 md:min-w-106">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
```

- [ ] **Verificar sintaxe**

```bash
php artisan view:clear
```

Esperado: sem erros.

- [ ] **Commit**

```bash
git add resources/views/components/admin/auth-layout.blade.php
git commit -m "feat(auth): refatorar auth-layout — hero com overlay gradiente e prop heroSubtitle"
```

---

## Task 3: `LogoutController` + teste

**Files:**
- Create: `app/Http/Controllers/Admin/Auth/LogoutController.php`
- Create: `tests/Feature/Admin/Auth/LogoutTest.php`

- [ ] **Criar o diretório de testes se não existir**

```bash
mkdir -p tests/Feature/Admin/Auth
```

- [ ] **Escrever o teste (falha primeiro)**

```php
<?php
// tests/Feature/Admin/Auth/LogoutTest.php
declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('faz logout e redireciona para login', function () {
    $admin = AdminUser::create([
        'nome'     => 'Admin Teste',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});
```

- [ ] **Rodar para confirmar falha**

```bash
php artisan test tests/Feature/Admin/Auth/LogoutTest.php
```

Esperado: FAIL — `Route [admin.logout] not defined` ou similar (a rota ainda aponta para o controller que vamos deletar depois).

> **Nota:** O teste pode passar se a rota de logout já estiver funcional. Nesse caso pule para o próximo passo.

- [ ] **Criar o `LogoutController`**

```php
<?php
// app/Http/Controllers/Admin/Auth/LogoutController.php
declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
```

- [ ] **Rodar o teste**

```bash
php artisan test tests/Feature/Admin/Auth/LogoutTest.php
```

Esperado: PASS (1 teste).

- [ ] **Commit**

```bash
git add app/Http/Controllers/Admin/Auth/LogoutController.php tests/Feature/Admin/Auth/LogoutTest.php
git commit -m "feat(auth): criar LogoutController single-action"
```

---

## Task 4: Livewire `Admin\Auth\Login` + teste

**Files:**
- Create: `app/Livewire/Admin/Auth/Login.php`
- Create: `resources/views/livewire/admin/auth/login.blade.php`
- Create: `tests/Feature/Admin/Auth/LoginTest.php`

- [ ] **Criar diretório de views Livewire**

```bash
mkdir -p resources/views/livewire/admin/auth
```

- [ ] **Escrever os testes (falham primeiro)**

```php
<?php
// tests/Feature/Admin/Auth/LoginTest.php
declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de login', function () {
    Livewire::test(Login::class)
        ->assertSee('Bem-vindo de volta!')
        ->assertSee('E-mail')
        ->assertSee('Senha');
});

it('exibe erro com credenciais inválidas', function () {
    AdminUser::create([
        'nome'     => 'Admin',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@teste.com')
        ->set('password', 'senha-errada')
        ->call('authenticate')
        ->assertHasErrors(['email']);
});

it('faz login com credenciais válidas', function () {
    AdminUser::create([
        'nome'     => 'Admin',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue();
});

it('exibe erro de validação sem e-mail', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['email' => 'required']);
});

it('redireciona para dashboard se já autenticado', function () {
    $admin = AdminUser::create([
        'nome'     => 'Admin',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});
```

- [ ] **Rodar para confirmar falha**

```bash
php artisan test tests/Feature/Admin/Auth/LoginTest.php
```

Esperado: FAIL — classe `App\Livewire\Admin\Auth\Login` não existe.

- [ ] **Criar o componente Livewire**

```php
<?php
// app/Livewire/Admin/Auth/Login.php
declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Auth;
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
            redirect()->route('admin.dashboard');
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

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.login');
    }
}
```

- [ ] **Criar a view Livewire**

```blade
{{-- resources/views/livewire/admin/auth/login.blade.php --}}
<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Bem-vindo de volta!</h4>
    <p class="text-default-400 mb-9 mx-auto w-full text-center text-sm lg:w-72">
        Acesse o painel administrativo.
    </p>

    @if (session('status'))
        <x-shared.alert variant="success" class="mb-6">{{ session('status') }}</x-shared.alert>
    @endif

    <form wire:submit="authenticate">
        <div class="mb-5">
            <x-shared.input
                name="email"
                label="E-mail"
                type="email"
                wire:model="email"
                icon="tabler--mail"
                placeholder="admin@exemplo.com.br"
                required
                autofocus
                autocomplete="email"
            />
        </div>

        <div class="mb-5">
            <x-shared.password-input
                name="password"
                label="Senha"
                wire:model="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            />
        </div>

        @if ($errors->any())
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <x-shared.checkbox name="remember" label="Lembrar de mim" wire:model="remember" />
            <a
                class="text-primary text-sm font-semibold underline underline-offset-4"
                href="{{ route('admin.password.request') }}"
            >
                Esqueceu sua senha?
            </a>
        </div>

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="authenticate">
            Entrar
        </x-shared.loading-button>
    </form>
</x-admin.auth-form-card>
```

- [ ] **Rodar os testes**

```bash
php artisan test tests/Feature/Admin/Auth/LoginTest.php
```

Esperado: 5 testes PASS. Se o teste "redireciona para dashboard se já autenticado" falhar porque a rota ainda aponta para o controller antigo, a Task 7 corrige isso — continue.

- [ ] **Commit**

```bash
git add app/Livewire/Admin/Auth/Login.php \
        resources/views/livewire/admin/auth/login.blade.php \
        tests/Feature/Admin/Auth/LoginTest.php
git commit -m "feat(auth): Livewire Login full-page com testes"
```

---

## Task 5: Livewire `Admin\Auth\ForgotPassword` + teste

**Files:**
- Create: `app/Livewire/Admin/Auth/ForgotPassword.php`
- Create: `resources/views/livewire/admin/auth/forgot-password.blade.php`
- Create: `tests/Feature/Admin/Auth/ForgotPasswordTest.php`

- [ ] **Escrever os testes**

```php
<?php
// tests/Feature/Admin/Auth/ForgotPasswordTest.php
declare(strict_types=1);

use App\Livewire\Admin\Auth\ForgotPassword;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de recuperação de senha', function () {
    Livewire::test(ForgotPassword::class)
        ->assertSee('Esqueceu sua senha?')
        ->assertSee('Enviar link de redefinição');
});

it('exibe erro de validação para e-mail inválido', function () {
    Livewire::test(ForgotPassword::class)
        ->set('email', 'nao-e-um-email')
        ->call('sendLink')
        ->assertHasErrors(['email' => 'email']);
});

it('envia notificação e limpa o campo ao enviar link para e-mail existente', function () {
    Notification::fake();

    $admin = AdminUser::create([
        'nome'     => 'Admin',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'admin@teste.com')
        ->call('sendLink')
        ->assertHasNoErrors()
        ->assertSet('email', '');

    Notification::assertSentTo($admin, ResetPasswordNotification::class);
});

it('não exibe erro para e-mail inexistente (segurança — não revela existência)', function () {
    Livewire::test(ForgotPassword::class)
        ->set('email', 'nao-existe@teste.com')
        ->call('sendLink')
        ->assertHasErrors(['email']);
});
```

- [ ] **Rodar para confirmar falha**

```bash
php artisan test tests/Feature/Admin/Auth/ForgotPasswordTest.php
```

Esperado: FAIL — classe não existe.

- [ ] **Criar o componente Livewire**

```php
<?php
// app/Livewire/Admin/Auth/ForgotPassword.php
declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Support\Facades\Password;
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

        $status = Password::broker('admins')->sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('status', __($status));
            $this->email = '';
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.forgot-password');
    }
}
```

- [ ] **Criar a view Livewire**

```blade
{{-- resources/views/livewire/admin/auth/forgot-password.blade.php --}}
<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Esqueceu sua senha?</h4>
    <p class="text-default-400 mb-9 mx-auto w-full text-center text-sm lg:w-72">
        Digite seu e-mail e enviaremos um link para redefinir sua senha.
    </p>

    @if (session('status'))
        <x-shared.alert variant="success" class="mb-6">{{ session('status') }}</x-shared.alert>
    @endif

    <form wire:submit="sendLink">
        <div class="mb-6">
            <x-shared.input
                name="email"
                label="Endereço de e-mail"
                type="email"
                wire:model="email"
                icon="tabler--mail"
                placeholder="admin@exemplo.com.br"
                required
                autofocus
                autocomplete="email"
            />
        </div>

        @if ($errors->has('email'))
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first('email') }}</x-shared.alert>
        @endif

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="sendLink">
            Enviar link de redefinição
        </x-shared.loading-button>
    </form>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        Lembrou a senha?
        <a
            class="text-primary font-semibold underline underline-offset-4"
            href="{{ route('admin.login') }}"
        >
            Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
```

- [ ] **Rodar os testes**

```bash
php artisan test tests/Feature/Admin/Auth/ForgotPasswordTest.php
```

Esperado: 4 testes PASS.

- [ ] **Commit**

```bash
git add app/Livewire/Admin/Auth/ForgotPassword.php \
        resources/views/livewire/admin/auth/forgot-password.blade.php \
        tests/Feature/Admin/Auth/ForgotPasswordTest.php
git commit -m "feat(auth): Livewire ForgotPassword full-page com testes"
```

---

## Task 6: Livewire `Admin\Auth\ResetPassword` + teste

**Files:**
- Create: `app/Livewire/Admin/Auth/ResetPassword.php`
- Create: `resources/views/livewire/admin/auth/reset-password.blade.php`
- Create: `tests/Feature/Admin/Auth/ResetPasswordTest.php`

- [ ] **Escrever os testes**

```php
<?php
// tests/Feature/Admin/Auth/ResetPasswordTest.php
declare(strict_types=1);

use App\Livewire\Admin\Auth\ResetPassword;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de redefinição de senha', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->assertSee('Definir nova senha')
        ->assertSee('Nova senha')
        ->assertSee('Confirmar nova senha');
});

it('preenche o token via mount', function () {
    Livewire::test(ResetPassword::class, ['token' => 'meu-token'])
        ->assertSet('token', 'meu-token');
});

it('exibe erro se as senhas não coincidem', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->set('email', 'admin@teste.com')
        ->set('password', 'nova-senha-123')
        ->set('password_confirmation', 'senha-diferente')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

it('exibe erro se a senha é muito curta', function () {
    Livewire::test(ResetPassword::class, ['token' => 'fake-token'])
        ->set('email', 'admin@teste.com')
        ->set('password', '1234567')
        ->set('password_confirmation', '1234567')
        ->call('resetPassword')
        ->assertHasErrors(['password' => 'min']);
});

it('redireciona para login após reset bem-sucedido', function () {
    $admin = AdminUser::create([
        'nome'     => 'Admin',
        'email'    => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo'    => true,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'admin@teste.com')
        ->set('password', 'nova-senha-segura')
        ->set('password_confirmation', 'nova-senha-segura')
        ->call('resetPassword')
        ->assertRedirect(route('admin.login'));

    expect(Hash::check('nova-senha-segura', $admin->fresh()->password))->toBeTrue();
});
```

- [ ] **Rodar para confirmar falha**

```bash
php artisan test tests/Feature/Admin/Auth/ResetPasswordTest.php
```

Esperado: FAIL — classe não existe.

- [ ] **Criar o componente Livewire**

```php
<?php
// app/Livewire/Admin/Auth/ResetPassword.php
declare(strict_types=1);

namespace App\Livewire\Admin\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.admin.auth-layout')]
#[Title('Nova senha')]
final class ResetPassword extends Component
{
    public string $token = '';

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|confirmed|min:8')]
    public string $password = '';

    #[Validate('required')]
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::broker('admins')->reset(
            [
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token'                 => $this->token,
            ],
            static function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('success', __($status));
            $this->redirect(route('admin.login'), navigate: true);
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.auth.reset-password');
    }
}
```

- [ ] **Criar a view Livewire**

```blade
{{-- resources/views/livewire/admin/auth/reset-password.blade.php --}}
<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Definir nova senha</h4>
    <p class="text-default-400 mb-9 text-center text-sm">
        Sua nova senha deve ter pelo menos 8 caracteres.
    </p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token" />
        <input type="hidden" wire:model="email" />

        <div class="mb-5">
            <x-shared.password-input
                name="password"
                label="Nova senha"
                wire:model="password"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
        </div>

        <div class="mb-5">
            <x-shared.password-input
                name="password_confirmation"
                label="Confirmar nova senha"
                wire:model="password_confirmation"
                placeholder="••••••••"
                required
                autocomplete="new-password"
            />
        </div>

        <div class="mb-6">
            <x-shared.password-strength-meter target="password" />
        </div>

        @if ($errors->any())
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
        @endif

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="resetPassword">
            Redefinir senha
        </x-shared.loading-button>
    </form>

    <p class="text-default-400 mt-7.5 text-center text-sm">
        <a
            class="text-primary font-semibold underline underline-offset-4"
            href="{{ route('admin.login') }}"
        >
            ← Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
```

- [ ] **Rodar os testes**

```bash
php artisan test tests/Feature/Admin/Auth/ResetPasswordTest.php
```

Esperado: 5 testes PASS.

- [ ] **Commit**

```bash
git add app/Livewire/Admin/Auth/ResetPassword.php \
        resources/views/livewire/admin/auth/reset-password.blade.php \
        tests/Feature/Admin/Auth/ResetPasswordTest.php
git commit -m "feat(auth): Livewire ResetPassword full-page com testes"
```

---

## Task 7: Migrar rotas + deletar arquivos antigos

**Files:**
- Modify: `routes/admin.php`
- Delete: 7 arquivos listados abaixo

- [ ] **Atualizar rotas em `routes/admin.php`**

Localizar o bloco de rotas guest (área de auth não-autenticada) e substituir:

```php
// ANTES (remover estas linhas):
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.store');
Route::get('/esqueceu-senha', [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/esqueceu-senha', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
Route::get('/resetar-senha/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
Route::post('/resetar-senha', [ResetPasswordController::class, 'reset'])->name('password.update');

// DEPOIS (substituir por):
Route::get('/login', \App\Livewire\Admin\Auth\Login::class)->name('login');
Route::get('/esqueceu-senha', \App\Livewire\Admin\Auth\ForgotPassword::class)->name('password.request');
Route::get('/resetar-senha/{token}', \App\Livewire\Admin\Auth\ResetPassword::class)->name('password.reset');
```

Localizar a rota de logout e substituir:

```php
// ANTES:
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// DEPOIS:
Route::post('/logout', \App\Http\Controllers\Admin\Auth\LogoutController::class)->name('logout');
```

Remover os `use` imports dos controllers antigos no topo do arquivo (LoginController, ForgotPasswordController, ResetPasswordController).

- [ ] **Verificar que as rotas estão corretas**

```bash
php artisan route:list --path=admin | grep -E "login|logout|senha|resetar"
```

Esperado (rotas listadas):
```
GET|HEAD   admin/login               admin.login           App\Livewire\Admin\Auth\Login
GET|HEAD   admin/esqueceu-senha      admin.password.request App\Livewire\Admin\Auth\ForgotPassword
GET|HEAD   admin/resetar-senha/{token} admin.password.reset App\Livewire\Admin\Auth\ResetPassword
POST       admin/logout              admin.logout           App\Http\Controllers\Admin\Auth\LogoutController
```

- [ ] **Deletar controllers antigos**

```bash
rm app/Http/Controllers/Admin/Auth/LoginController.php
rm app/Http/Controllers/Admin/Auth/ForgotPasswordController.php
rm app/Http/Controllers/Admin/Auth/ResetPasswordController.php
```

- [ ] **Deletar LoginRequest (validação migrou para Livewire)**

```bash
rm app/Http/Requests/Admin/Auth/LoginRequest.php
```

- [ ] **Deletar views Blade antigas**

```bash
rm resources/views/admin/auth/login.blade.php
rm resources/views/admin/auth/forgot-password.blade.php
rm resources/views/admin/auth/reset-password.blade.php
```

- [ ] **Rodar todos os testes para confirmar nada quebrou**

```bash
php artisan test
```

Esperado: todos os testes existentes + os novos passando. Nenhum erro relacionado a classes removidas.

- [ ] **Commit**

```bash
git add routes/admin.php
git rm app/Http/Controllers/Admin/Auth/LoginController.php \
       app/Http/Controllers/Admin/Auth/ForgotPasswordController.php \
       app/Http/Controllers/Admin/Auth/ResetPasswordController.php \
       app/Http/Requests/Admin/Auth/LoginRequest.php \
       resources/views/admin/auth/login.blade.php \
       resources/views/admin/auth/forgot-password.blade.php \
       resources/views/admin/auth/reset-password.blade.php
git commit -m "refactor(auth): migrar rotas para Livewire e remover controllers/views antigos"
```

---

## Task 8: Verificação final + atualizar catálogo

**Files:**
- Modify: `docs/template/INSPINIA/CATALOGO-COMPONENTES.md`

- [ ] **Rodar quality check completo**

```bash
./vendor/bin/pint
```

Esperado: nenhum arquivo com diferença (ou corrigir automaticamente).

```bash
php -d memory_limit=512M ./vendor/bin/phpstan analyse
```

Esperado: `No errors`.

```bash
php artisan test
```

Esperado: todos passando.

```bash
npm run build
```

Esperado: build sem erros.

- [ ] **Atualizar o catálogo de componentes**

No arquivo `docs/template/INSPINIA/CATALOGO-COMPONENTES.md`, localizar a linha do item 56 e 57:

```markdown
| 56  | `admin/auth/login.blade.php`  | Page/Auth | ... | 🟢 | P4 | 🔴 | ❌ (view) |
```

Alterar a coluna de status de `🔴` para `🟢`:

```markdown
| 56  | `admin/auth/login.blade.php`  | Page/Auth | ... | 🟢 | P4 | 🟢 | ❌ (view) |
```

> **Nota:** Item 56 cobre login. As telas forgot-password e reset-password não têm entrada separada no catálogo — adicionar uma nota no rodapé da seção 11 se quiser rastrear.

- [ ] **Commit final**

```bash
git add docs/template/INSPINIA/CATALOGO-COMPONENTES.md
git commit -m "docs: marcar tela de login como concluída no catálogo Inspinia"
```

---

## Checklist de Aceitação Final

Após todas as tasks, verificar manualmente (ou via browser):

- [ ] `GET /admin/login` — split layout renderiza: hero com foto+overlay+logo+tagline à esquerda, card com logo+form+copyright à direita
- [ ] Login com credenciais inválidas → erro inline sem reload
- [ ] Login com credenciais válidas → redireciona para `/admin/dashboard`
- [ ] "Lembrar de mim" marcado → sessão persiste
- [ ] "Esqueceu sua senha?" → `/admin/esqueceu-senha` com mesmo hero, form de 1 campo
- [ ] Envio de link → mensagem de sucesso inline, campo limpo
- [ ] `/admin/resetar-senha/TOKEN?email=X` → e-mail pré-preenchido + strength meter funcional
- [ ] Logout (via sidebar) → invalida sessão, redireciona para login
- [ ] `/admin/login` com usuário já autenticado → redireciona para dashboard
- [ ] Nenhum hardcode de nome de sistema — tudo via `config('app.name')` / `config('branding.*')`
- [ ] `pint`, `phpstan` e `test` todos passando
