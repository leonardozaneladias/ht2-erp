# Perfil & Conta — Plano de Implementação

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Substituir os placeholders `admin.perfil.*`/`admin.conta.*` por uma área "Minha conta" tabulada (Perfil · Segurança · Preferências) com avatar, troca de senha, histórico de logins e preferências de idioma/fuso — tudo self-service.

**Architecture:** Uma página Livewire `MinhaConta` espelha o padrão de abas da tela de Configurações (Preline `data-hs-tab` + `request()->query('aba')`) e monta componentes Livewire aninhados por aba. A aba Segurança reaproveita o `SegurancaConta` (2FA) existente, agora como painel. Login passa a ser registrado por um listener no evento `Auth\Events\Login` (histórico + `last_login_*`), pulando personificação.

**Tech Stack:** Laravel 13, PHP 8.4, Livewire 4, Inspinia (Tailwind 4), PostgreSQL, Pest 4 (SQLite em memória nos testes).

**Ambiente/convenções:** testes `ddev artisan test --filter=...`; Pint `ddev exec ./vendor/bin/pint --dirty`; PHPStan `ddev exec ./vendor/bin/phpstan analyse`. `declare(strict_types=1)`, type hints, PT-BR, sem CSS customizado. Helpers de teste em `tests/Pest.php`: `criarAdminUser($email='user@teste.com')` (senha `password`, nome "Usuário Teste"), `criarRoleAdmin($name,$nivel)`.

**⚠️ Lições recorrentes (todo implementador deve seguir):**

1. `Classe::class` SEM o `use` correspondente vira a STRING do nome curto → erros enganosos ("ComponentNotFound"/"does not exist"). Adicione import + uso na MESMA edição.
2. O Pint (pre-commit + hook) remove imports não usados ENTRE edições. Nunca deixe um `use` sem uso numa edição intermediária.
3. `Livewire::test()` NÃO enxerga `withSession`; use `session([...])` direto.
4. `AuthorizationException` numa ação Livewire vira 403 → asserta `->assertForbidden()`.
5. Commitar SÓ os arquivos da task (nunca `yarn.lock` nem `.workflows/`).
6. COMPLETE todos os passos (rodar teste + commit) antes de reportar.

**Nomes de componentes Livewire (classe → tag):** `PerfilConta` → `admin.conta.perfil-conta`; `TrocarSenha` → `admin.conta.trocar-senha`; `SegurancaConta` → `admin.conta.seguranca-conta`; `HistoricoLogins` → `admin.conta.historico-logins`; `PreferenciasConta` → `admin.conta.preferencias-conta`.

---

## Task 1: Camada de dados (migrations + LoginHistory + AdminUser)

**Files:**

- Create: `database/migrations/2026_06_05_190000_add_locale_timezone_to_admin_users.php`
- Create: `database/migrations/2026_06_05_190100_create_admin_login_history.php`
- Create: `app/Models/LoginHistory.php`
- Modify: `app/Models/AdminUser.php`
- Test: `tests/Feature/Admin/Conta/ContaDataLayerTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/ContaDataLayerTest.php`:

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\LoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('persiste locale/timezone e relaciona o histórico de logins', function (): void {
    $user = criarAdminUser('dados@teste.com');
    $user->forceFill(['locale' => 'en', 'timezone' => 'UTC'])->save();

    LoginHistory::create([
        'admin_user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'PHPUnit',
    ]);

    $fresh = $user->fresh();

    expect($fresh->locale)->toBe('en')
        ->and($fresh->timezone)->toBe('UTC')
        ->and($fresh->loginHistory()->count())->toBe(1)
        ->and($fresh->loginHistory()->first()->ip_address)->toBe('203.0.113.7');
});

it('urlAvatar retorna null sem avatar e a URL pública quando há caminho', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('avatar@teste.com');

    expect($user->urlAvatar())->toBeNull();

    $user->forceFill(['avatar_url' => 'avatars/foto.jpg'])->save();

    expect($user->fresh()->urlAvatar())->toBe(Storage::disk('public')->url('avatars/foto.jpg'));
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=ContaDataLayerTest` → FALHA (coluna/model/método inexistentes).

- [ ] **Step 3: Migration de colunas** — `database/migrations/2026_06_05_190000_add_locale_timezone_to_admin_users.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->string('locale', 10)->nullable()->after('avatar_url');
            $table->string('timezone', 64)->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropColumn(['locale', 'timezone']);
        });
    }
};
```

- [ ] **Step 4: Migration do histórico** — `database/migrations/2026_06_05_190100_create_admin_login_history.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_login_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['admin_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_history');
    }
};
```

- [ ] **Step 5: Model LoginHistory** — `app/Models/LoginHistory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico de logins de um AdminUser (append-only, sem updated_at).
 *
 * @property int $admin_user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class LoginHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'admin_login_history';

    protected $fillable = [
        'admin_user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
```

- [ ] **Step 6: AdminUser** — em `app/Models/AdminUser.php`:

(a) adicione `'locale'` e `'timezone'` ao `$fillable` (após `'avatar_url'`).

(b) adicione o import do facade Storage no topo (junto aos demais `use`):

```php
use Illuminate\Support\Facades\Storage;
```

(c) adicione, logo após o método `permissionGrants()`, a relação + o resolvedor de avatar (import e uso na mesma edição garantem que o Pint mantenha o `use Storage`):

```php
    /**
     * @return HasMany<LoginHistory, $this>
     */
    public function loginHistory(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'admin_user_id');
    }

    /**
     * URL pública do avatar (ou null para cair no fallback de iniciais).
     */
    public function urlAvatar(): ?string
    {
        $caminho = $this->avatar_url;

        if ($caminho === null || $caminho === '') {
            return null;
        }

        return Storage::disk('public')->url($caminho);
    }
```

(`HasMany` já está importado no arquivo; `LoginHistory` está no mesmo namespace `App\Models`, então não precisa de `use`.)

- [ ] **Step 7: Verde** — `ddev artisan test --filter=ContaDataLayerTest` → PASSA (2 testes).

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_06_05_190000_add_locale_timezone_to_admin_users.php database/migrations/2026_06_05_190100_create_admin_login_history.php app/Models/LoginHistory.php app/Models/AdminUser.php tests/Feature/Admin/Conta/ContaDataLayerTest.php
git commit -m "feat(admin): camada de dados de perfil/conta (locale, timezone, histórico de logins)"
```

---

## Task 2: Componente `x-shared.avatar`

**Files:**

- Create: `resources/views/components/shared/avatar.blade.php`
- Test: `tests/Feature/Admin/Conta/AvatarComponentTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/AvatarComponentTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza a imagem quando há src', function (): void {
    $html = Blade::render('<x-shared.avatar name="João Silva" src="https://cdn/x.jpg" />');

    expect($html)->toContain('<img')->toContain('https://cdn/x.jpg');
});

it('renderiza iniciais quando não há src', function (): void {
    $html = Blade::render('<x-shared.avatar name="João Silva" />');

    expect($html)->not->toContain('<img')->toContain('JS');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=AvatarComponentTest` → FALHA (componente inexistente).

- [ ] **Step 3: Componente** — `resources/views/components/shared/avatar.blade.php`:

```blade
@props ([
    'name' => '',
    'src' => null,
    'size' => 'size-8',
])

@php
    $iniciais = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
        ->filter()
        ->take(2)
        ->map(static fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    $iniciais = $iniciais !== '' ? $iniciais : '?';

    $cores = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-secondary'];
    $cor = $cores[(int) (crc32((string) $name) % count($cores))];
@endphp

@if ($src)
    <img alt="{{ $name }}" src="{{ $src }}" {{ $attributes->class([$size, 'rounded-full object-cover']) }} />
@else
    <span
        aria-label="{{ $name }}"
        {{ $attributes->class([$size, $cor, 'inline-flex items-center justify-center rounded-full text-sm font-semibold text-white']) }}
    >
        {{ $iniciais }}
    </span>
@endif
```

- [ ] **Step 4: Verde** — `ddev artisan test --filter=AvatarComponentTest` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/shared/avatar.blade.php tests/Feature/Admin/Conta/AvatarComponentTest.php
git commit -m "feat(admin): componente x-shared.avatar com fallback de iniciais"
```

---

## Task 3: Listener de login (histórico + last_login)

**Files:**

- Create: `app/Listeners/RegistrarLoginAdmin.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Admin/Conta/RegistroLoginTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/RegistroLoginTest.php`:

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\LoginHistory;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registra o login do guard admin e atualiza last_login', function (): void {
    $user = criarAdminUser('login@teste.com');

    event(new Login('admin', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(1)
        ->and($user->fresh()->last_login_at)->not->toBeNull();
});

it('não registra durante personificação', function (): void {
    $user = criarAdminUser('login@teste.com');
    app(ImpersonationContext::class)->iniciar(999, 'suporte');

    event(new Login('admin', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(0);
});

it('ignora guards que não sejam admin', function (): void {
    $user = criarAdminUser('login@teste.com');

    event(new Login('web', $user, false));

    expect(LoginHistory::where('admin_user_id', $user->id)->count())->toBe(0);
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=RegistroLoginTest` → FALHA (listener não registra nada).

- [ ] **Step 3: Listener** — `app/Listeners/RegistrarLoginAdmin.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\LoginHistory;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Registra cada login real do guard admin: atualiza last_login_at/ip e grava
 * uma linha no histórico. Logins de personificação (act-as) são ignorados —
 * o contexto já está ativo neste ponto (ver IniciarImpersonationAction).
 */
final class RegistrarLoginAdmin
{
    public function __construct(
        private readonly Request $request,
        private readonly ImpersonationContext $impersonation,
    ) {}

    public function handle(Login $event): void
    {
        if ($event->guard !== 'admin' || ! $event->user instanceof AdminUser) {
            return;
        }

        if ($this->impersonation->ativo()) {
            return;
        }

        $usuario = $event->user;
        $ip = $this->request->ip();

        $usuario->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->save();

        LoginHistory::create([
            'admin_user_id' => $usuario->getKey(),
            'ip_address' => $ip,
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 500),
        ]);
    }
}
```

- [ ] **Step 4: Registrar o listener** — em `app/Providers/AppServiceProvider.php`, adicione os imports (junto aos demais `use`) e, no FINAL do método `boot()`, registre o listener (import + uso na mesma edição):

Imports:

```php
use App\Listeners\RegistrarLoginAdmin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
```

No fim do `boot()`:

```php
        // Registra cada login real do guard admin (histórico + last_login).
        Event::listen(Login::class, RegistrarLoginAdmin::class);
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=RegistroLoginTest` → PASSA (3 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/RegistrarLoginAdmin.php app/Providers/AppServiceProvider.php tests/Feature/Admin/Conta/RegistroLoginTest.php
git commit -m "feat(admin): registra histórico de login e last_login via listener"
```

---

## Task 4: Aba Perfil (`PerfilConta` + `AtualizarPerfilAction`)

**Files:**

- Create: `app/Actions/Admin/Conta/AtualizarPerfilAction.php`
- Create: `app/Livewire/Admin/Conta/PerfilConta.php`
- Create: `resources/views/livewire/admin/conta/perfil-conta.blade.php`
- Test: `tests/Feature/Admin/Conta/PerfilContaTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/PerfilContaTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\PerfilConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('atualiza o nome do próprio usuário', function (): void {
    $user = criarAdminUser('perfil@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)
        ->set('nome', 'Nome Novo')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($user->fresh()->nome)->toBe('Nome Novo');
});

it('faz upload do avatar e grava o caminho', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('perfil@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)
        ->set('nome', 'Usuário Teste')
        ->set('avatar', UploadedFile::fake()->image('foto.jpg'))
        ->call('salvar')
        ->assertHasNoErrors();

    $caminho = $user->fresh()->avatar_url;

    expect($caminho)->not->toBeNull();
    Storage::disk('public')->assertExists($caminho);
});

it('remove o avatar atual', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('perfil@teste.com');
    $user->forceFill(['avatar_url' => 'avatars/antiga.jpg'])->save();
    Storage::disk('public')->put('avatars/antiga.jpg', 'x');
    $this->actingAs($user, 'admin');

    Livewire::test(PerfilConta::class)->call('removerAvatar');

    expect($user->fresh()->avatar_url)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/antiga.jpg');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=PerfilContaTest` → FALHA.

- [ ] **Step 3: Action** — `app/Actions/Admin/Conta/AtualizarPerfilAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin\Conta;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\Settings\SettingsFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Atualiza o perfil do próprio usuário (nome + avatar). O avatar é gravado no
 * disco público, substituindo o anterior; removê-lo volta ao fallback de iniciais.
 */
final class AtualizarPerfilAction
{
    public function __construct(private readonly SettingsFileUploadService $upload) {}

    public function execute(AdminUser $usuario, string $nome, ?UploadedFile $avatar): void
    {
        $usuario->nome = $nome;

        if ($avatar !== null) {
            $usuario->avatar_url = $this->upload->substituir($avatar, (string) $usuario->avatar_url, 'avatars');
        }

        $usuario->save();
    }

    public function removerAvatar(AdminUser $usuario): void
    {
        if ($usuario->avatar_url !== null && $usuario->avatar_url !== '') {
            Storage::disk('public')->delete($usuario->avatar_url);
        }

        $usuario->avatar_url = null;
        $usuario->save();
    }
}
```

- [ ] **Step 4: Componente** — `app/Livewire/Admin/Conta/PerfilConta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use App\Actions\Admin\Conta\AtualizarPerfilAction;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Aba "Perfil" da Minha Conta: avatar, nome e resumo (leitura) de papéis,
 * empresas e último login. Opera sempre sobre o próprio usuário autenticado.
 */
class PerfilConta extends Component
{
    use WithFileUploads;

    public string $nome = '';

    public mixed $avatar = null;

    public function mount(): void
    {
        $this->nome = (string) $this->usuario()->getAttribute('nome');
    }

    public function salvar(AtualizarPerfilAction $action): void
    {
        $this->validate([
            'nome' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $action->execute($this->usuario(), $this->nome, $this->avatar);
        $this->reset('avatar');

        $this->dispatch('toast', variant: 'success', message: 'Perfil atualizado.');
    }

    public function removerAvatar(AtualizarPerfilAction $action): void
    {
        $action->removerAvatar($this->usuario());

        $this->dispatch('toast', variant: 'success', message: 'Foto removida.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.perfil-conta', [
            'usuario' => $this->usuario(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
```

- [ ] **Step 5: View** — `resources/views/livewire/admin/conta/perfil-conta.blade.php`:

```blade
<x-shared.card title="Perfil" subtitle="Sua foto, nome e identificação no painel.">
    <form wire:submit="salvar" class="grid gap-5">
        <div class="flex items-center gap-4">
            @if ($avatar)
                <img src="{{ $avatar->temporaryUrl() }}" alt="Prévia" class="size-16 rounded-full object-cover" />
            @else
                <x-shared.avatar :name="$usuario->nome" :src="$usuario->urlAvatar()" size="size-16" />
            @endif

            <div class="flex flex-col gap-2">
                <input type="file" wire:model="avatar" accept="image/png,image/jpeg,image/webp" class="text-sm" />
                @if ($usuario->urlAvatar())
                    <button
                        type="button"
                        wire:click="removerAvatar"
                        class="text-danger text-left text-xs hover:underline"
                    >
                        Remover foto
                    </button>
                @endif
                @error ('avatar')
                    <small class="text-danger text-xs">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <x-shared.input name="nome" label="Nome" wire:model="nome" required />

        <div class="grid gap-1">
            <span class="text-default-500 text-sm font-medium">E-mail</span>
            <span class="text-default-700">{{ $usuario->email }}</span>
        </div>

        <div class="border-default-200 grid gap-2 border-t pt-4 text-sm">
            <div class="flex justify-between">
                <span class="text-default-500">Papéis globais</span>
                <span class="text-default-700">{{ $usuario->getRoleNames()->join(', ') ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-default-500">Último login</span>
                <span class="text-default-700">
                    {{ $usuario->last_login_at?->timezone($usuario->timezone ?? config('app.timezone'))->translatedFormat('d/m/Y \à\s H:i') ?? '—' }}
                </span>
            </div>
        </div>

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar perfil</x-shared.loading-button
            >
        </div>
    </form>
</x-shared.card>
```

- [ ] **Step 6: Verde** — `ddev artisan test --filter=PerfilContaTest` → PASSA (3 testes). Se "ComponentNotFound", confira os imports do teste e o nome da tag.

- [ ] **Step 7: Commit**

```bash
git add app/Actions/Admin/Conta/AtualizarPerfilAction.php app/Livewire/Admin/Conta/PerfilConta.php resources/views/livewire/admin/conta/perfil-conta.blade.php tests/Feature/Admin/Conta/PerfilContaTest.php
git commit -m "feat(admin): aba de perfil com avatar e nome"
```

---

## Task 5: Aba Segurança — Trocar senha (`TrocarSenha`)

**Files:**

- Create: `app/Livewire/Admin/Conta/TrocarSenha.php`
- Create: `resources/views/livewire/admin/conta/trocar-senha.blade.php`
- Test: `tests/Feature/Admin/Conta/TrocarSenhaTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/TrocarSenhaTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\TrocarSenha;
use HT2ML\Core\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('troca a senha com a senha atual correta', function (): void {
    $user = criarAdminUser('senha@teste.com'); // senha = "password"
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'password')
        ->set('novaSenha', 'NovaSenha9')
        ->set('novaSenha_confirmation', 'NovaSenha9')
        ->call('trocar')
        ->assertHasNoErrors();

    expect(Hash::check('NovaSenha9', $user->fresh()->password))->toBeTrue()
        ->and(Activity::where('log_name', 'conta')->where('event', 'senha_alterada')->exists())->toBeTrue();
});

it('rejeita a senha atual incorreta', function (): void {
    $user = criarAdminUser('senha@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'errada')
        ->set('novaSenha', 'NovaSenha9')
        ->set('novaSenha_confirmation', 'NovaSenha9')
        ->call('trocar')
        ->assertHasErrors('senhaAtual');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('rejeita nova senha fraca', function (): void {
    $user = criarAdminUser('senha@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(TrocarSenha::class)
        ->set('senhaAtual', 'password')
        ->set('novaSenha', 'fraca')
        ->set('novaSenha_confirmation', 'fraca')
        ->call('trocar')
        ->assertHasErrors('novaSenha');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=TrocarSenhaTest` → FALHA.

- [ ] **Step 3: Componente** — `app/Livewire/Admin/Conta/TrocarSenha.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use HT2ML\Core\Models\AdminUser;
use App\Support\Settings\PasswordPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Aba "Segurança" — troca da própria senha. A senha atual é a trava (sem modal
 * de reconfirmação). Aplica a política de senha configurada (PasswordPolicy).
 */
class TrocarSenha extends Component
{
    public string $senhaAtual = '';

    public string $novaSenha = '';

    public string $novaSenha_confirmation = '';

    public function trocar(): void
    {
        $this->validate([
            'senhaAtual' => ['required', 'current_password:admin'],
            'novaSenha' => ['required', 'confirmed', 'different:senhaAtual', PasswordPolicy::rule()],
        ], [
            'senhaAtual.current_password' => 'A senha atual está incorreta.',
            'novaSenha.different' => 'A nova senha deve ser diferente da atual.',
        ]);

        $usuario = $this->usuario();
        $usuario->update(['password' => $this->novaSenha]);

        session()->regenerate();

        activity('conta')
            ->causedBy($usuario)
            ->event('senha_alterada')
            ->log('Senha alterada pelo próprio usuário');

        $this->reset('senhaAtual', 'novaSenha', 'novaSenha_confirmation');
        $this->dispatch('toast', variant: 'success', message: 'Senha alterada.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.trocar-senha', [
            'politica' => PasswordPolicy::descricao(),
        ]);
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
```

- [ ] **Step 4: View** — `resources/views/livewire/admin/conta/trocar-senha.blade.php`:

```blade
<x-shared.card title="Senha" subtitle="Troque sua senha de acesso ao painel.">
    <form wire:submit="trocar" class="grid max-w-md gap-4">
        <x-shared.password-input
            name="senhaAtual"
            label="Senha atual"
            wire:model="senhaAtual"
            autocomplete="current-password"
        />

        <x-shared.password-input
            name="novaSenha"
            label="Nova senha"
            wire:model="novaSenha"
            autocomplete="new-password"
            :hint="$politica"
        />

        <x-shared.password-input
            name="novaSenha_confirmation"
            label="Confirmar nova senha"
            wire:model="novaSenha_confirmation"
            autocomplete="new-password"
        />

        <div class="flex justify-end">
            <x-shared.loading-button target="trocar" icon="tabler--lock">Alterar senha</x-shared.loading-button>
        </div>
    </form>
</x-shared.card>
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=TrocarSenhaTest` → PASSA (3 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Conta/TrocarSenha.php resources/views/livewire/admin/conta/trocar-senha.blade.php tests/Feature/Admin/Conta/TrocarSenhaTest.php
git commit -m "feat(admin): troca de senha na conta"
```

---

## Task 6: Aba Segurança — Histórico de logins (`HistoricoLogins`)

**Files:**

- Create: `app/Livewire/Admin/Conta/HistoricoLogins.php`
- Create: `resources/views/livewire/admin/conta/historico-logins.blade.php`
- Test: `tests/Feature/Admin/Conta/HistoricoLoginsTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/HistoricoLoginsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\HistoricoLogins;
use HT2ML\Core\Models\LoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lista os logins do próprio usuário', function (): void {
    $user = criarAdminUser('hist@teste.com');
    LoginHistory::create(['admin_user_id' => $user->id, 'ip_address' => '198.51.100.9', 'user_agent' => 'Firefox']);
    $this->actingAs($user, 'admin');

    Livewire::test(HistoricoLogins::class)
        ->assertOk()
        ->assertSee('198.51.100.9');
});

it('não mostra logins de outro usuário', function (): void {
    $user = criarAdminUser('hist@teste.com');
    $outro = criarAdminUser('outro@teste.com');
    LoginHistory::create(['admin_user_id' => $outro->id, 'ip_address' => '203.0.113.50', 'user_agent' => 'Chrome']);
    $this->actingAs($user, 'admin');

    Livewire::test(HistoricoLogins::class)->assertDontSee('203.0.113.50');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=HistoricoLoginsTest` → FALHA.

- [ ] **Step 3: Componente** — `app/Livewire/Admin/Conta/HistoricoLogins.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use HT2ML\Core\Models\AdminUser;
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
```

- [ ] **Step 4: View** — `resources/views/livewire/admin/conta/historico-logins.blade.php`:

```blade
<x-shared.card title="Histórico de logins" subtitle="Seus 10 acessos mais recentes.">
    @if ($registros->isEmpty())
        <x-shared.empty-state
            size="sm"
            icon="tabler--login-2"
            title="Sem registros"
            description="Seus próximos acessos aparecerão aqui."
        />
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-default-500 border-default-200 border-b text-left">
                        <th class="py-2 pe-4 font-medium">Data</th>
                        <th class="py-2 pe-4 font-medium">IP</th>
                        <th class="py-2 font-medium">Navegador</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($registros as $registro)
                        <tr class="border-default-100 border-b">
                            <td class="text-default-700 py-2 pe-4">
                                {{ $registro->created_at?->timezone($usuario->timezone ?? config('app.timezone'))->translatedFormat('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-default-700 py-2 pe-4">{{ $registro->ip_address ?? '—' }}</td>
                            <td class="text-default-500 py-2">
                                {{ \Illuminate\Support\Str::limit($registro->user_agent ?? '—', 60) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-shared.card>
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=HistoricoLoginsTest` → PASSA (2 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Conta/HistoricoLogins.php resources/views/livewire/admin/conta/historico-logins.blade.php tests/Feature/Admin/Conta/HistoricoLoginsTest.php
git commit -m "feat(admin): histórico de logins na conta"
```

---

## Task 7: Aba Preferências (`PreferenciasConta`)

**Files:**

- Create: `app/Livewire/Admin/Conta/PreferenciasConta.php`
- Create: `resources/views/livewire/admin/conta/preferencias-conta.blade.php`
- Test: `tests/Feature/Admin/Conta/PreferenciasContaTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/PreferenciasContaTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\PreferenciasConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('salva idioma e fuso do usuário', function (): void {
    $user = criarAdminUser('pref@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PreferenciasConta::class)
        ->set('locale', 'en')
        ->set('timezone', 'UTC')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($user->fresh()->locale)->toBe('en')
        ->and($user->fresh()->timezone)->toBe('UTC');
});

it('rejeita idioma fora da lista', function (): void {
    $user = criarAdminUser('pref@teste.com');
    $this->actingAs($user, 'admin');

    Livewire::test(PreferenciasConta::class)
        ->set('locale', 'xx')
        ->call('salvar')
        ->assertHasErrors('locale');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=PreferenciasContaTest` → FALHA.

- [ ] **Step 3: Componente** — `app/Livewire/Admin/Conta/PreferenciasConta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Aba "Preferências" — idioma e fuso horário do próprio usuário. Nulo herda o
 * padrão da instância. O locale é aplicado por request (middleware) e o fuso só
 * na exibição de datas.
 */
class PreferenciasConta extends Component
{
    public ?string $locale = null;

    public ?string $timezone = null;

    public function mount(): void
    {
        $usuario = $this->usuario();
        $this->locale = $usuario->locale;
        $this->timezone = $usuario->timezone;
    }

    public function salvar(): void
    {
        $this->validate([
            'locale' => ['nullable', Rule::in(array_keys($this->locales()))],
            'timezone' => ['nullable', Rule::in(array_keys($this->timezones()))],
        ]);

        $this->usuario()->forceFill([
            'locale' => $this->locale ?: null,
            'timezone' => $this->timezone ?: null,
        ])->save();

        $this->dispatch('toast', variant: 'success', message: 'Preferências salvas.');
    }

    public function render(): View
    {
        return view('livewire.admin.conta.preferencias-conta', [
            'locales' => $this->locales(),
            'timezones' => $this->timezones(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function locales(): array
    {
        return [
            'pt_BR' => 'Português (Brasil)',
            'en' => 'English (US)',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function timezones(): array
    {
        return [
            'America/Sao_Paulo' => 'Brasília (São Paulo)',
            'America/Manaus' => 'Manaus',
            'America/Cuiaba' => 'Cuiabá',
            'America/Campo_Grande' => 'Campo Grande',
            'America/Belem' => 'Belém',
            'America/Fortaleza' => 'Fortaleza',
            'America/Recife' => 'Recife',
            'America/Bahia' => 'Salvador',
            'America/Rio_Branco' => 'Rio Branco',
            'America/Noronha' => 'Fernando de Noronha',
            'UTC' => 'UTC',
        ];
    }

    private function usuario(): AdminUser
    {
        $usuario = Auth::guard('admin')->user();

        assert($usuario instanceof AdminUser);

        return $usuario;
    }
}
```

- [ ] **Step 4: View** — `resources/views/livewire/admin/conta/preferencias-conta.blade.php`:

```blade
<x-shared.card title="Preferências" subtitle="Idioma e fuso horário aplicados à sua experiência.">
    <form wire:submit="salvar" class="grid max-w-md gap-4">
        <x-shared.select
            name="locale"
            label="Idioma"
            wire:model="locale"
            :value="$locale"
            :options="$locales"
            placeholder="Padrão da instância"
        />

        <x-shared.select
            name="timezone"
            label="Fuso horário"
            wire:model="timezone"
            :value="$timezone"
            :options="$timezones"
            placeholder="Padrão da instância"
        />

        <div class="flex justify-end">
            <x-shared.loading-button target="salvar" icon="tabler--device-floppy">
                Salvar preferências</x-shared.loading-button
            >
        </div>
    </form>
</x-shared.card>
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=PreferenciasContaTest` → PASSA (2 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Conta/PreferenciasConta.php resources/views/livewire/admin/conta/preferencias-conta.blade.php tests/Feature/Admin/Conta/PreferenciasContaTest.php
git commit -m "feat(admin): preferências de idioma e fuso na conta"
```

---

## Task 8: Middleware de aplicação das preferências

**Files:**

- Create: `app/Http/Middleware/AplicarPreferenciasUsuario.php`
- Modify: `routes/admin.php` (adicionar o middleware ao grupo autenticado)
- Test: `tests/Feature/Admin/Conta/PreferenciasMiddlewareTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/PreferenciasMiddlewareTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplica o locale do usuário na request autenticada', function (): void {
    $user = criarAdminUser('loc@teste.com');
    $user->forceFill(['locale' => 'en'])->save();

    $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('mantém o locale padrão quando o usuário não definiu', function (): void {
    $user = criarAdminUser('loc@teste.com');

    $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    expect(app()->getLocale())->toBe(config('app.locale'));
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=PreferenciasMiddlewareTest` → o 1º teste FALHA (locale não aplicado).

- [ ] **Step 3: Middleware** — `app/Http/Middleware/AplicarPreferenciasUsuario.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use HT2ML\Core\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica as preferências do usuário autenticado à request: define o locale.
 * O fuso horário é aplicado apenas na exibição de datas (não altera o app.timezone
 * global, que afeta a gravação). Nulo herda o padrão da instância.
 */
final class AplicarPreferenciasUsuario
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('admin');

        if ($usuario instanceof AdminUser && $usuario->locale !== null && $usuario->locale !== '') {
            App::setLocale($usuario->locale);
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar no grupo autenticado** — em `routes/admin.php`, no array de middleware do grupo autenticado (a linha que começa com `Route::prefix('admin')->name('admin.')->middleware([...`), adicione `App\Http\Middleware\AplicarPreferenciasUsuario::class` logo APÓS `App\Http\Middleware\DefinirContextoTenant::class`:

```php
Route::prefix('admin')->name('admin.')->middleware([App\Http\Middleware\EnsureSystemConfigured::class, 'admin.auth', App\Http\Middleware\GarantirContaAtiva::class, App\Http\Middleware\EncerrarImpersonationExpirada::class, App\Http\Middleware\CheckInactivity::class, App\Http\Middleware\EnsureTwoFactorEnabled::class, App\Http\Middleware\DefinirContextoTenant::class, App\Http\Middleware\AplicarPreferenciasUsuario::class])->group(function () use ($placeholder): void {
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=PreferenciasMiddlewareTest` → PASSA (2 testes).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/AplicarPreferenciasUsuario.php routes/admin.php tests/Feature/Admin/Conta/PreferenciasMiddlewareTest.php
git commit -m "feat(admin): aplica locale do usuário por request"
```

---

## Task 9: Adaptar `SegurancaConta` para painel

**Files:**

- Modify: `app/Livewire/Admin/Conta/SegurancaConta.php`
- Modify: `resources/views/livewire/admin/conta/seguranca-conta.blade.php`

- [ ] **Step 1: Remover layout/título do componente** — em `app/Livewire/Admin/Conta/SegurancaConta.php`, remova os atributos `#[Layout(...)]` e `#[Title(...)]` (linhas logo acima de `class SegurancaConta`) e os imports `use Livewire\Attributes\Layout;` e `use Livewire\Attributes\Title;` (ficam sem uso). O componente passa a ser sempre aninhado na aba Segurança.

- [ ] **Step 2: Remover o page-header da view** — em `resources/views/livewire/admin/conta/seguranca-conta.blade.php`, remova o bloco do topo:

```blade
<x-admin.page-header title="Segurança da conta" subtitle="Proteja seu acesso com verificação em duas etapas (2FA)." />
```

O restante (alert de recovery codes + card do 2FA + `@include('admin.partials.confirms-password')`) permanece. A `<div>` raiz e o `@include` continuam.

- [ ] **Step 3: Rodar os testes do 2FA (devem continuar verdes)** — estes usam `Livewire::test(SegurancaConta::class)` diretamente, então não dependem da rota nem do layout:

Run: `ddev artisan test --filter='TwoFactorTest|TravasSegurancaContaTest|ConfirmacaoSenhaTest'`
Expected: PASS (exceto o caso "força a configuração de 2FA" do `TwoFactorTest`, que será ajustado na Task 10 — se rodar isolado aqui ele AINDA passa, pois a rota `admin.conta.seguranca` continua existindo como redirect só após a Task 10; rode o filtro acima e confirme verde).

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Admin/Conta/SegurancaConta.php resources/views/livewire/admin/conta/seguranca-conta.blade.php
git commit -m "refactor(admin): SegurancaConta como painel da aba (sem layout próprio)"
```

---

## Task 10: Shell `MinhaConta` + rotas + 2FA enforcement

**Files:**

- Create: `app/Livewire/Admin/Conta/MinhaConta.php`
- Create: `resources/views/livewire/admin/conta/minha-conta.blade.php`
- Modify: `routes/admin.php` (rota `admin.conta` + redirects dos placeholders)
- Modify: `app/Http/Middleware/EnsureTwoFactorEnabled.php`
- Modify: `tests/Feature/Admin/Auth/TwoFactorTest.php` (assertion de redirect)
- Test: `tests/Feature/Admin/Conta/MinhaContaTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/MinhaContaTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renderiza a área da conta com as três abas', function (): void {
    $user = criarAdminUser('conta@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.conta'))
        ->assertOk()
        ->assertSee('Perfil')
        ->assertSee('Segurança')
        ->assertSee('Preferências');
});

it('seleciona a aba via query string', function (): void {
    $user = criarAdminUser('conta@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.conta', ['aba' => 'seguranca']))
        ->assertOk();
});

it('redireciona os placeholders antigos para a conta', function (): void {
    $user = criarAdminUser('conta@teste.com');
    $this->actingAs($user, 'admin');

    $this->get(route('admin.perfil.show'))->assertRedirect(route('admin.conta'));
    $this->get(route('admin.conta.edit'))->assertRedirect(route('admin.conta'));
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=MinhaContaTest` → FALHA (rota `admin.conta` inexistente).

- [ ] **Step 3: Componente shell** — `app/Livewire/Admin/Conta/MinhaConta.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Conta;

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
```

- [ ] **Step 4: View shell** — `resources/views/livewire/admin/conta/minha-conta.blade.php`:

```blade
<div>
    <x-admin.page-header title="Minha conta" subtitle="Seus dados, segurança e preferências." />

    <x-shared.tab-nav>
        @foreach ($abas as $aba)
            <x-shared.tab-trigger :id="$aba['value']" :icon="$aba['icon']" :active="$aba['value'] === $abaAtiva">
                {{ $aba['label'] }}
            </x-shared.tab-trigger>
        @endforeach
    </x-shared.tab-nav>

    <div class="mt-5">
        <x-shared.tab-panel id="perfil" :active="$abaAtiva === 'perfil'">
            <livewire:admin.conta.perfil-conta />
        </x-shared.tab-panel>

        <x-shared.tab-panel id="seguranca" :active="$abaAtiva === 'seguranca'">
            <div class="grid gap-6">
                <livewire:admin.conta.trocar-senha />
                <livewire:admin.conta.seguranca-conta />
                <livewire:admin.conta.historico-logins />
            </div>
        </x-shared.tab-panel>

        <x-shared.tab-panel id="preferencias" :active="$abaAtiva === 'preferencias'">
            <livewire:admin.conta.preferencias-conta />
        </x-shared.tab-panel>
    </div>
</div>
```

- [ ] **Step 5: Rotas** — em `routes/admin.php`, substitua o bloco dos placeholders (os grupos `Route::prefix('perfil')...` e `Route::prefix('conta')...`, atuais linhas ~102-110) por:

```php
    Route::get('/conta', App\Livewire\Admin\Conta\MinhaConta::class)->name('conta');

    Route::prefix('perfil')->name('perfil.')->group(function (): void {
        Route::redirect('/', '/admin/conta')->name('show');
    });

    Route::prefix('conta')->name('conta.')->group(function () use ($placeholder): void {
        Route::redirect('/editar', '/admin/conta')->name('edit');
        Route::redirect('/seguranca', '/admin/conta?aba=seguranca')->name('seguranca');
        Route::get('/notificacoes', static fn (): Response => $placeholder('Preferências de Notificação'))->name('notificacoes');
    });
```

(A rota `admin.conta.seguranca` antiga, que usava `SegurancaConta::class`, vira redirect; `notificacoes` segue placeholder — é o Subsistema 2.)

- [ ] **Step 6: 2FA enforcement** — em `app/Http/Middleware/EnsureTwoFactorEnabled.php`, atualize as duas referências à rota de segurança:

Troque `&& ! $request->routeIs('admin.conta.seguranca')` por:

```php
            && ! $request->routeIs('admin.conta')
```

E troque `return redirect()->route('admin.conta.seguranca')` por:

```php
            return redirect()->route('admin.conta', ['aba' => 'seguranca'])
```

- [ ] **Step 7: Ajustar o teste de enforcement** — em `tests/Feature/Admin/Auth/TwoFactorTest.php`, no caso "força a configuração de 2FA quando a política exige", troque:

```php
        ->assertRedirect(route('admin.conta.seguranca'));
```

por:

```php
        ->assertRedirect(route('admin.conta', ['aba' => 'seguranca']));
```

- [ ] **Step 8: Verde** — rode:

```bash
ddev artisan test --filter='MinhaContaTest|TwoFactorTest'
```

Expected: PASS. Se "ComponentNotFound [admin.conta.perfil-conta]" ou similar, confira que as tags batem com os nomes das classes (kebab-case do nome completo da classe).

- [ ] **Step 9: Commit**

```bash
git add app/Livewire/Admin/Conta/MinhaConta.php resources/views/livewire/admin/conta/minha-conta.blade.php routes/admin.php app/Http/Middleware/EnsureTwoFactorEnabled.php tests/Feature/Admin/Auth/TwoFactorTest.php tests/Feature/Admin/Conta/MinhaContaTest.php
git commit -m "feat(admin): área Minha Conta com abas + redirects e enforcement de 2FA"
```

---

## Task 11: Integração na topbar e sidebar

**Files:**

- Modify: `resources/views/components/admin/topbar.blade.php`
- Modify: `resources/views/components/admin/sidebar.blade.php`
- Test: `tests/Feature/Admin/Conta/ContaChromeTest.php`

- [ ] **Step 1: Teste que falha** — `tests/Feature/Admin/Conta/ContaChromeTest.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('a topbar aponta os itens de perfil/conta para admin.conta', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $resposta = $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    $resposta->assertSee(route('admin.conta'), false);
});

it('renderiza o avatar com iniciais quando não há foto', function (): void {
    $user = criarAdminUser('chrome@teste.com'); // nome "Usuário Teste"

    $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('UT'); // iniciais de "Usuário Teste"
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=ContaChromeTest` → o 2º teste FALHA (ainda usa `<img>` com avatar default).

- [ ] **Step 3: Topbar** — em `resources/views/components/admin/topbar.blade.php`:

(a) substitua o `<img>` do avatar do usuário (atuais linhas ~189-193) por:

```blade
<x-shared.avatar :name="$displayUser['nome']" :src="$user?->urlAvatar()" size="size-8" class="lg:me-3" />
```

(b) nos itens do dropdown, troque os destinos:

- `:href="route('admin.perfil.show')"` → `:href="route('admin.conta')"`
- `:href="route('admin.conta.edit')"` → `:href="route('admin.conta', ['aba' => 'preferencias'])"`

(o item "Notificações" → `admin.conta.notificacoes` permanece — Subsistema 2.)

- [ ] **Step 4: Sidebar** — em `resources/views/components/admin/sidebar.blade.php`:

(a) substitua o `<img>` do avatar (atuais linhas ~83-87) por:

```blade
<x-shared.avatar :name="$displayUser['nome']" :src="$user?->urlAvatar()" size="size-9" class="mb-3" />
```

(b) troque os destinos dos links/itens:

- `href="{{ route('admin.perfil.show') }}"` → `href="{{ route('admin.conta') }}"`
- `:href="route('admin.perfil.show')"` → `:href="route('admin.conta')"`
- `:href="route('admin.conta.edit')"` → `:href="route('admin.conta', ['aba' => 'preferencias'])"`

> Verifique que `$user` está disponível na sidebar (o topbar define `$user = auth('admin')->user()` no `@php` inicial). Se a sidebar não tiver esse `$user`, adicione `@php $user = auth('admin')->user(); @endphp` no topo da sidebar antes do uso.

- [ ] **Step 5: Verde** — `ddev artisan test --filter=ContaChromeTest` → PASSA (2 testes).

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/admin/topbar.blade.php resources/views/components/admin/sidebar.blade.php tests/Feature/Admin/Conta/ContaChromeTest.php
git commit -m "feat(admin): topbar/sidebar usam x-shared.avatar e apontam para Minha Conta"
```

---

## Task 12: Portão de qualidade + documentação

**Files:**

- Modify: `docs/multi-empresa.md` ou `README.md` (nota curta — opcional)

- [ ] **Step 1: Suíte completa** — `ddev artisan test` → tudo verde (sem regressões nas suítes de 2FA/impersonation/segurança).

- [ ] **Step 2: Pint** — `ddev exec ./vendor/bin/pint --dirty` → sem alterações pendentes (ou aplica e segue).

- [ ] **Step 3: PHPStan** — `ddev exec ./vendor/bin/phpstan analyse` → sem erros. Se acusar `Cannot call method ... on string` em colunas datetime, adicione o `@property \Illuminate\Support\Carbon|null $coluna` no model correspondente (padrão já usado em `AdminUser`).

- [ ] **Step 4: Nota de doc (opcional)** — registre numa linha em `docs/` que `/admin/conta` agora concentra Perfil/Segurança/Preferências e que `admin.perfil.*`/`admin.conta.edit`/`admin.conta.seguranca` são redirects. Commit:

```bash
git add -A -- docs README.md 2>/dev/null; git commit -m "docs(admin): nota sobre a área Minha Conta" || true
```

---

## Self-review (cobertura do spec)

- Decisão 1 (IA `/admin/conta` com abas) → Tasks 9, 10.
- Decisão 2 (componentes aninhados, reaproveita SegurancaConta) → Tasks 9, 10.
- Decisão 3 (avatar+nome / trocar senha / preferências / histórico) → Tasks 2, 4, 5, 6, 7.
- Decisão 4 (histórico de logins, sem encerrar sessões) → Tasks 1, 3, 6.
- Registro de login (listener, pula personificação) → Task 3.
- Aplicação de preferências (locale por request; fuso só na exibição) → Tasks 7, 8 (+ formatação por fuso nas views de Perfil/Histórico).
- `x-shared.avatar` (iniciais) usado em topbar/sidebar/perfil → Tasks 2, 4, 11.
- Redirects dos placeholders + enforcement de 2FA atualizado → Task 10.
- Self-service (sempre o próprio usuário; sem cross-user) → componentes resolvem via `Auth::guard('admin')->user()`.
- Não-objetivos (trocar e-mail; sessões ativas; notificações; realtime) → fora do plano (Subsistema 2 cobre notificações).
