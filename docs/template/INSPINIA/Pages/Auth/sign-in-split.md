# Sign In — Split Layout (Login Admin 14.1)

**Categoria:** Page / Auth
**Origem Inspinia:** `resources/views/auth-split/sign-in.blade.php`
**Plugins JS:** Nenhum (só Livewire + Preline)
**Uso no ArtFinal:** Tela 14.1 — Login Admin

---

## Descrição

Layout de login dividido em 2 colunas: **imagem** à esquerda (hero/background) e **formulário** à direita. Responsivo — em mobile a imagem esconde, só form. Uso no admin do ArtFinal conforme PRD §14.1.

---

## Código Original (Inspinia — essência)

```html
<div class="flex h-full min-h-screen w-full">
    <!-- Left: hero image (hidden mobile) -->
    <div class="hidden w-full md:block">
        <div class="relative h-full overflow-hidden bg-[url('/images/auth.jpg')] bg-cover bg-center"></div>
    </div>

    <!-- Right: form card -->
    <div class="min-w-full md:max-w-118 md:min-w-106">
        <div class="card flex min-h-screen flex-col justify-between rounded-none p-12.5">
            <!-- Logo -->
            <div class="text-center">
                <img class="flex dark:hidden" src="/images/logo-black.png" />
                <img class="hidden dark:flex" src="/images/logo.png" />
            </div>

            <!-- Form -->
            <div>
                <h4 class="text-center text-lg font-bold">Welcome to Admin</h4>
                <p class="text-default-400 text-center">Sign in to continue.</p>
                <form>
                    <!-- email + password inputs -->
                </form>
            </div>

            <!-- Footer -->
            <p class="text-default-400 text-center">© 2026 Inspinia</p>
        </div>
    </div>
</div>
```

---

## View Proposta (ArtFinal)

**Não é Blade component** — é uma **view full-page** do Livewire.

### Arquivo

```
resources/views/admin/auth/login.blade.php
```

### Livewire component

```php
// app/Livewire/Admin/Auth/Login.php
#[Layout('components.admin.auth-layout')]
#[Title('Entrar')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:6')]
    public string $password = '';

    public bool $remember = false;

    public function entrar(): void
    {
        $this->validate();

        if (! Auth::guard('admin')->attempt([
            'email' => $this->email,
            'password' => $this->password,
            'ativo' => true,
        ], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => __('Credenciais inválidas ou usuário inativo.'),
            ]);
        }

        session()->regenerate();
        $admin = Auth::guard('admin')->user();
        $admin->update(['last_login_at' => now(), 'last_login_ip' => request()->ip()]);

        redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.auth.login');
    }
}
```

### Layout mínimo (`<x-admin.auth-layout>`)

```blade
{{-- resources/views/components/admin/auth-layout.blade.php --}}
@props (['title'])

<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" data-skin="default">
<head>
    <meta charset="utf-8" />
    <title>{{ $title }} | Portal ArtFinal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <x-admin.partials.theme-bootstrap />
    @vite (['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body>
    {{ $slot }}
    @livewireScripts
    <x-shared.toast-container />
</body>
</html>
```

### View da Login

```blade
{{-- resources/views/livewire/admin/auth/login.blade.php --}}
<div class="min-h-screen flex h-full w-full">
    {{-- Hero image --}}
    <div class="hidden md:block w-full">
        <div class="relative h-full bg-[url('/images/auth.jpg')] bg-cover bg-center">
            <div
                class="absolute inset-0 bg-linear-to-t from-primary/80 via-primary/40 to-transparent flex items-end p-12"
            >
                <div class="text-white">
                    <h2 class="text-3xl font-bold mb-2">Portal ArtFinal</h2>
                    <p class="text-white/80">Gestão completa de formaturas.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="min-w-full md:min-w-106 md:max-w-118">
        <div class="card flex min-h-screen flex-col justify-between rounded-none p-12">
            <div class="text-center">
                <a href="{{ url('/') }}">
                    <img
                        alt="ArtFinal"
                        class="mx-auto h-12 flex dark:hidden"
                        src="{{ asset('images/admin/logo-dark.png') }}"
                    />
                    <img
                        alt="ArtFinal"
                        class="mx-auto h-12 hidden dark:flex"
                        src="{{ asset('images/admin/logo.png') }}"
                    />
                </a>
            </div>

            <div>
                <h4 class="font-bold text-lg text-center mb-1">Bem-vindo de volta</h4>
                <p class="text-default-400 text-center text-sm mb-8">Entre para acessar o painel.</p>

                <form wire:submit="entrar">
                    <x-shared.input
                        name="email"
                        label="E-mail"
                        type="email"
                        icon="tabler--mail"
                        wire:model="email"
                        required
                        autofocus
                    />

                    <x-shared.password-input name="password" label="Senha" wire:model="password" required />

                    <div class="flex items-center justify-between mb-5">
                        <x-shared.checkbox name="remember" label="Lembrar-me" wire:model="remember" />
                        <a
                            href="{{ route('admin.password.request') }}"
                            class="text-default-400 text-sm underline underline-offset-4"
                        >
                            Esqueci minha senha
                        </a>
                    </div>

                    <x-shared.loading-button variant="primary" type="submit" target="entrar" block>
                        Entrar
                    </x-shared.loading-button>
                </form>
            </div>

            <p class="text-default-400 text-center text-xs">© {{ date('Y') }} Portal ArtFinal · Todos os direitos reservados.</p>
        </div>
    </div>
</div>
```

---

## Mapeamento no PRD

| Tela        | Seção PRD |
| ----------- | --------- |
| Login Admin | 14.1      |

---

## Classificação

| Critério         | Valor           |
| ---------------- | --------------- |
| **Vai usar**     | 🟢 Sim          |
| **Prioridade**   | P4 (Sprint 15)  |
| **Complexidade** | Média           |
| **Status**       | 🔴 Não iniciado |

---

## Notas de Adaptação

1. **Layout próprio** `<x-admin.auth-layout>` — sem sidebar/topbar, diferente do `<x-admin.layout>` master
2. **Livewire full-page** — `#[Layout('components.admin.auth-layout')]`
3. **Throttle Laravel:** adicionar middleware `throttle:5,10` na rota de login (5 tentativas em 10 min, conforme PRD §14.1)
4. **`ativo = true`** obrigatório no guard attempt
5. **`session()->regenerate()`** — previne session fixation
6. **`last_login_at` e `last_login_ip`** — registro de auditoria (PRD §14.1)
7. **Imagem hero** — usar foto de formatura real (gerenciado, não stock)
8. **Sem link "Criar conta"** — admin não tem self-service; usuários criados via 14.18
