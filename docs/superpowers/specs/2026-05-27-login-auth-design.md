# Spec: Telas de Auth com Inspinia + Livewire

**Data:** 2026-05-27
**Status:** Aprovada — pronta para implementação

---

## Contexto

As três telas de auth do admin (`login`, `forgot-password`, `reset-password`) existem como views Blade puras com controllers PHP+POST. O objetivo é:

1. Converter para **Livewire full-page** (validação reativa, loading states, sem reload)
2. Refatorar `x-admin.auth-layout` para suportar o **hero melhorado** (overlay gradiente + logo + tagline)
3. Criar o componente reutilizável **`x-admin.auth-form-card`** (painel direito branco)
4. Preservar toda a lógica de segurança existente (broker `admins`, session regenerate, CSRF)

---

## Arquitetura

### Hierarquia de componentes

```
x-admin.auth-layout          (modificado — adiciona prop $heroSubtitle)
├── Painel esquerdo (hero)   — foto + overlay gradiente + logo + app.name + heroSubtitle
└── {{ $slot }}              — caller coloca x-admin.auth-form-card aqui

x-admin.auth-form-card       (novo)
├── Logo light/dark          — branding.logo_dark_path / branding.logo_path
├── {{ $slot }}              — conteúdo do form (título, inputs, botão)
└── Copyright                — © {{ date('Y') }} {{ config('app.name') }}

Livewire (3 componentes full-page, cada um com #[Layout('components.admin.auth-layout')]):
├── Admin\Auth\Login
├── Admin\Auth\ForgotPassword
└── Admin\Auth\ResetPassword
```

### Fluxo de uma request

```
GET /login
  → Livewire\Admin\Auth\Login (full-page)
  → render() → view('livewire.admin.auth.login')
  → view usa x-admin.auth-form-card com o form
  → x-admin.auth-layout envolve tudo (split layout)
```

---

## Componentes

### 1. `x-admin.auth-layout` (modificar)

**Arquivo:** `resources/views/components/admin/auth-layout.blade.php`

**Props a adicionar:**
```php
@props([
    'title'        => null,
    'heroSubtitle' => 'Painel administrativo.',
])
```

**Painel hero esquerdo — HTML atualizado:**
```blade
<div class="hidden w-full md:block">
    <div class="relative h-full overflow-hidden bg-[url('/images/auth.jpg')] bg-cover bg-center bg-no-repeat">
        <div class="absolute inset-0 bg-linear-to-t from-black/60 via-black/20 to-transparent"></div>
        <div class="absolute bottom-0 left-0 p-9">
            <img alt="{{ config('app.name') }}" class="mb-5 h-7" src="{{ asset(config('branding.logo_path')) }}" />
            <p class="text-lg font-bold text-white">{{ config('app.name') }}</p>
            <p class="mt-1 text-sm text-white/60">{{ $heroSubtitle }}</p>
        </div>
    </div>
</div>
```

**Painel direito — simplificado (delega ao auth-form-card):**
```blade
<div class="min-w-full md:max-w-118 md:min-w-106">
    {{ $slot }}
</div>
```

O `$slot` agora recebe o `x-admin.auth-form-card` completo (não mais apenas o form interno).

---

### 2. `x-admin.auth-form-card` (novo)

**Arquivo:** `resources/views/components/admin/auth-form-card.blade.php`

**Sem props** — tudo via `config('branding')` e `config('app.name')`.

```blade
<div class="card relative flex min-h-screen flex-col justify-between rounded-none p-12.5">
    {{-- Logo --}}
    <div class="mb-7.5 flex flex-col items-center justify-center">
        <a href="{{ route('admin.login') }}">
            <img alt="{{ config('app.name') }}"
                 class="flex h-8 dark:hidden"
                 src="{{ asset(config('branding.logo_dark_path')) }}" />
            <img alt="{{ config('app.name') }}"
                 class="hidden h-8 dark:flex"
                 src="{{ asset(config('branding.logo_path')) }}" />
        </a>
    </div>

    {{-- Conteúdo (form) --}}
    <div>{{ $slot }}</div>

    {{-- Copyright --}}
    <p class="text-default-400 mt-7.5 text-center text-sm">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>
```

---

## Livewire Components

### 3. `Admin\Auth\Login`

**Arquivo:** `app/Livewire/Admin/Auth/Login.php`

```php
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
        $this->redirect(session()->pull('url.intended', route('admin.dashboard')), navigate: true);
    }
}
```

**View:** `resources/views/livewire/admin/auth/login.blade.php`

```blade
<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Bem-vindo de volta!</h4>
    <p class="text-default-400 mb-9 text-center text-sm">Acesse o painel administrativo.</p>

    @if (session('status'))
        <x-shared.alert variant="success" class="mb-6">{{ session('status') }}</x-shared.alert>
    @endif

    <form wire:submit="authenticate">
        <div class="mb-5">
            <x-shared.input name="email" label="E-mail" type="email"
                wire:model="email" icon="tabler--mail"
                placeholder="admin@exemplo.com.br" required autofocus autocomplete="email" />
        </div>
        <div class="mb-5">
            <x-shared.password-input name="password" label="Senha"
                wire:model="password" placeholder="••••••••"
                required autocomplete="current-password" />
        </div>

        @if ($errors->any())
            <x-shared.alert variant="danger" class="mb-5">{{ $errors->first() }}</x-shared.alert>
        @endif

        <div class="mb-6 flex items-center justify-between">
            <x-shared.checkbox name="remember" label="Lembrar de mim" wire:model="remember" />
            <a class="text-primary text-sm font-semibold underline underline-offset-4"
               href="{{ route('admin.password.request') }}">
                Esqueceu sua senha?
            </a>
        </div>

        <x-shared.loading-button type="submit" class="w-full py-3" wire:target="authenticate">
            Entrar
        </x-shared.loading-button>
    </form>
</x-admin.auth-form-card>
```

---

### 4. `Admin\Auth\ForgotPassword`

**Arquivo:** `app/Livewire/Admin/Auth/ForgotPassword.php`

```php
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
}
```

**View:** `resources/views/livewire/admin/auth/forgot-password.blade.php`

```blade
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
            <x-shared.input name="email" label="Endereço de e-mail" type="email"
                wire:model="email" icon="tabler--mail"
                placeholder="admin@exemplo.com.br" required autofocus autocomplete="email" />
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
        <a class="text-primary font-semibold underline underline-offset-4"
           href="{{ route('admin.login') }}">
            Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
```

---

### 5. `Admin\Auth\ResetPassword`

**Arquivo:** `app/Livewire/Admin/Auth/ResetPassword.php`

```php
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
            ['email' => $this->email, 'password' => $this->password,
             'password_confirmation' => $this->password_confirmation, 'token' => $this->token],
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
}
```

**View:** `resources/views/livewire/admin/auth/reset-password.blade.php`

```blade
<x-admin.auth-form-card>
    <h4 class="text-default-900 mb-2 text-center text-lg font-bold">Definir nova senha</h4>
    <p class="text-default-400 mb-9 text-center text-sm">Sua nova senha deve ter pelo menos 8 caracteres.</p>

    <form wire:submit="resetPassword">
        <input type="hidden" wire:model="token" />
        <input type="hidden" wire:model="email" />

        <div class="mb-5">
            <x-shared.password-input name="password" label="Nova senha"
                wire:model="password" placeholder="••••••••"
                required autocomplete="new-password" />
        </div>
        <div class="mb-5">
            <x-shared.password-input name="password_confirmation" label="Confirmar nova senha"
                wire:model="password_confirmation" placeholder="••••••••"
                required autocomplete="new-password" />
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
        <a class="text-primary font-semibold underline underline-offset-4"
           href="{{ route('admin.login') }}">
            ← Voltar para o login
        </a>
    </p>
</x-admin.auth-form-card>
```

---

## LogoutController (novo — substitui login controller no logout)

**Arquivo:** `app/Http/Controllers/Admin/Auth/LogoutController.php`

O logout permanece como controller POST (CSRF, sem Livewire) — padrão mais seguro.

```php
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

---

## Rotas (`routes/admin.php`)

```php
// Antes:
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.store');
Route::get('/esqueceu-senha', [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/esqueceu-senha', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
Route::get('/resetar-senha/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
Route::post('/resetar-senha', [ResetPasswordController::class, 'reset'])->name('password.update');
// ...
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Depois:
Route::get('/login', \HT2ML\Core\Livewire\Admin\Auth\Login::class)->name('login');
Route::get('/esqueceu-senha', \HT2ML\Core\Livewire\Admin\Auth\ForgotPassword::class)->name('password.request');
Route::get('/resetar-senha/{token}', \HT2ML\Core\Livewire\Admin\Auth\ResetPassword::class)->name('password.reset');
// ...
Route::post('/logout', LogoutController::class)->name('logout');
```

Rotas POST de `/login`, `/esqueceu-senha` e `/resetar-senha` são removidas — Livewire gerencia internamente.
`login.store` e `password.email` e `password.update` não existem mais como rotas nomeadas.

---

## Arquivos

### Criar
| Arquivo | Descrição |
|---|---|
| `app/Livewire/Admin/Auth/Login.php` | Componente Livewire full-page |
| `app/Livewire/Admin/Auth/ForgotPassword.php` | Componente Livewire full-page |
| `app/Livewire/Admin/Auth/ResetPassword.php` | Componente Livewire full-page |
| `app/Http/Controllers/Admin/Auth/LogoutController.php` | Controller single-action de logout |
| `resources/views/components/admin/auth-form-card.blade.php` | Novo componente Blade |
| `resources/views/livewire/admin/auth/login.blade.php` | View Livewire |
| `resources/views/livewire/admin/auth/forgot-password.blade.php` | View Livewire |
| `resources/views/livewire/admin/auth/reset-password.blade.php` | View Livewire |

### Modificar
| Arquivo | O que muda |
|---|---|
| `resources/views/components/admin/auth-layout.blade.php` | Adiciona prop `$heroSubtitle`, refatora hero esquerdo e simplifica slot direito |
| `routes/admin.php` | Troca rotas de auth para Livewire + LogoutController |
| `docs/template/INSPINIA/CATALOGO-COMPONENTES.md` | Item 56 status 🔴 → 🟢 |

### Deletar
| Arquivo | Motivo |
|---|---|
| `app/Http/Controllers/Admin/Auth/LoginController.php` | Substituído por Livewire + LogoutController |
| `app/Http/Controllers/Admin/Auth/ForgotPasswordController.php` | Substituído por Livewire |
| `app/Http/Controllers/Admin/Auth/ResetPasswordController.php` | Substituído por Livewire |
| `app/Http/Requests/Admin/Auth/LoginRequest.php` | Validação migra para Livewire `#[Validate]` |
| `resources/views/admin/auth/login.blade.php` | Substituída por view Livewire |
| `resources/views/admin/auth/forgot-password.blade.php` | Substituída por view Livewire |
| `resources/views/admin/auth/reset-password.blade.php` | Substituída por view Livewire |

---

## Critérios de aceitação

- [ ] `/admin/login` renderiza split layout: hero esquerdo (foto+overlay+logo+tagline) + card direito (logo+form+copyright)
- [ ] Login com credenciais inválidas exibe erro inline sem reload de página
- [ ] Login com credenciais válidas redireciona para `/admin/dashboard`
- [ ] "Lembrar de mim" persiste sessão
- [ ] "Esqueceu sua senha?" abre `/admin/esqueceu-senha` — mesmo layout, card com 1 campo
- [ ] Link de reset enviado exibe mensagem de sucesso inline
- [ ] `/admin/resetar-senha/{token}` exibe e-mail pré-preenchido + 2 campos de senha + strength meter
- [ ] Senha fraca exibe feedback no strength meter
- [ ] Reset com token inválido/expirado exibe erro inline
- [ ] Reset com sucesso redireciona para login com flash de sucesso
- [ ] Logout (via form POST) invalida sessão e redireciona para login
- [ ] Usuário já autenticado em `/admin/login` é redirecionado para dashboard
- [ ] `x-admin.auth-form-card` pode ser usado em qualquer view futura de auth
- [ ] Nenhuma referência a nomes hardcoded — tudo via `config('app.name')` e `config('branding.*')`
- [ ] `pint`, `phpstan` e `php artisan test` passam sem erros

---

## Fora do escopo

- Throttle/rate limit na rota de login (próximo plano de segurança)
- Registro de `last_login_at` / `last_login_ip` (próximo plano de auditoria)
- 2FA / autenticação de dois fatores
- CRUD de usuários admin
