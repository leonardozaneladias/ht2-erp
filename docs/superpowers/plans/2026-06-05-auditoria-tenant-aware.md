# Auditoria tenant-aware — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tornar o `activity_log` ciente de tenant (grava `empresa_id`/`filial_id`), cobrir os eventos de autenticação hoje não logados, e isolar a tela de auditoria por empresa (com visão cross-empresa para super-admin / `auditoria.todas-empresas`).

**Architecture:** Colunas reais em `activity_log` + model custom `HT2ML\Core\Models\Activity`. Um invokable `CarimbarContextoNaAtividade` (registrado em `Activity::creating`) carimba o tenant em toda atividade e mantém o `impersonado_por` da impersonation. Um serviço `AuditoriaSeguranca` centraliza os eventos de autenticação, chamado explicitamente nos pontos genuínos (login/2FA/logout/reset). A `AuditoriaTable` isola por empresa no `datasource` conforme privilégio.

**Tech Stack:** Laravel 13, PHP 8.4, Livewire 4, PowerGrid, spatie/laravel-activitylog v5, Pest 4, Pint, PHPStan nível 6.

**Spec:** `docs/superpowers/specs/2026-06-05-auditoria-tenant-aware-design.md`

**Branch:** `feat/auditoria-tenant`

**Comandos** (containers DDEV web/db OK):

- Testes: `ddev artisan test --filter='<nome>'`
- Pint: `ddev exec ./vendor/bin/pint --dirty`
- PHPStan: `ddev exec ./vendor/bin/phpstan analyse`

> Convenções (CLAUDE.md): `declare(strict_types=1)`; type hints/return types; serviços API-ready; PT-BR nas descrições; `activity_log` é append-only.
>
> **Lições do épico anterior (aplicar):** (1) `$this->withSession([...])` NÃO é visto por `Livewire::test()` — escreva `session([...])`. (2) Em teste de componente Livewire, `AuthorizationException` vira 403 → `assertForbidden()`. (3) activitylog v5: ler propriedade com `$activity->getProperty('chave')`. (4) Em testes HTTP (`$this->get/post`), `withSession` funciona normalmente. (5) Privilégio `super-admin` já passa em `can('qualquer-permissão')` (bypass do Gate via AccessResolver), então `->can('auditoria.todas-empresas')` cobre o super-admin sem checagem extra. (6) Pint remove imports não usados no commit — adicione import + uso na mesma edição.

---

## Estrutura de arquivos

**Criar:**

- `database/migrations/2026_06_05_160000_add_tenant_to_activity_log.php` — colunas `empresa_id`/`filial_id`.
- `app/Models/Activity.php` — model custom (relações `empresa`/`filial`).
- `app/Support/Audit/CarimbarContextoNaAtividade.php` — invokable que carimba tenant + impersonação.
- `app/Services/Admin/AuditoriaSeguranca.php` — eventos de autenticação.
- `tests/Feature/Admin/Auditoria/{CarimboContexto,AuditoriaSeguranca,AuditoriaIsolamento}Test.php`.

**Modificar:**

- `config/activitylog.php` — `activity_model`.
- `config/access.php` — permissão `auditoria.todas-empresas`.
- `app/Providers/AppServiceProvider.php` — registrar o invokable; remover o closure inline.
- `app/Livewire/Admin/Auth/Login.php`, `TwoFactorChallenge.php`, `ForgotPassword.php`, `ResetPassword.php` — chamar o serviço.
- `app/Http/Controllers/Admin/Auth/LogoutController.php` — logout genuíno.
- `app/Livewire/Admin/Auditoria/AuditoriaTable.php` — model custom, isolamento, coluna/filtro empresa.

---

## Task 1: Colunas de tenant + model custom Activity

**Files:**

- Create: `database/migrations/2026_06_05_160000_add_tenant_to_activity_log.php`
- Create: `app/Models/Activity.php`
- Modify: `config/activitylog.php:7,45`
- Test: `tests/Feature/Admin/Auditoria/CarimboContextoTest.php` (parte 1)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa o model custom e resolve a relação empresa do activity_log', function (): void {
    expect(config('activitylog.activity_model'))->toBe(Activity::class);

    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);

    $log = activity('test')->log('evento');
    expect($log)->toBeInstanceOf(Activity::class);

    $log->empresa_id = $empresa->id;
    $log->save();

    $fresh = Activity::findOrFail($log->id);
    expect($fresh->empresa)->not->toBeNull()
        ->and($fresh->empresa->nome)->toBe('Acme');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='CarimboContextoTest'`
Expected: FAIL — `config('activitylog.activity_model')` ainda é o do spatie / coluna `empresa_id` inexistente.

- [ ] **Step 3: Migration**

`database/migrations/2026_06_05_160000_add_tenant_to_activity_log.php`:

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
        Schema::table('activity_log', function (Blueprint $table): void {
            // Sem FK: o log é append-only e deve sobreviver à exclusão da empresa/filial.
            $table->unsignedBigInteger('empresa_id')->nullable()->after('properties')->index();
            $table->unsignedBigInteger('filial_id')->nullable()->after('empresa_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn(['empresa_id', 'filial_id']);
        });
    }
};
```

- [ ] **Step 4: Model custom**

`app/Models/Activity.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity do projeto: estende o model do spatie/laravel-activitylog para expor
 * o contexto de tenant (empresa_id/filial_id) gravado em cada registro.
 */
class Activity extends SpatieActivity
{
    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return BelongsTo<Filial, $this>
     */
    public function filial(): BelongsTo
    {
        return $this->belongsTo(Filial::class);
    }
}
```

- [ ] **Step 5: Apontar o config para o model custom**

Em `config/activitylog.php`, trocar o import da linha 7:

```php
use HT2ML\Core\Models\Activity;
```

(A linha 45 `'activity_model' => Activity::class,` passa a resolver `HT2ML\Core\Models\Activity`. As linhas 5-6, que importam `CleanActivityLogAction`/`LogActivityAction` do spatie, permanecem.)

- [ ] **Step 6: Run test to verify it passes**

Run: `ddev artisan migrate --force && ddev artisan test --filter='CarimboContextoTest'`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_06_05_160000_add_tenant_to_activity_log.php app/Models/Activity.php config/activitylog.php tests/Feature/Admin/Auditoria/CarimboContextoTest.php
git commit -m "feat(admin): colunas de tenant e model custom no activity_log"
```

---

## Task 2: Carimbo de contexto (refatorar o listener)

**Files:**

- Create: `app/Support/Audit/CarimbarContextoNaAtividade.php`
- Modify: `app/Providers/AppServiceProvider.php` (substituir o closure inline em `boot()`)
- Test: `tests/Feature/Admin/Auditoria/CarimboContextoTest.php` (adicionar casos)

- [ ] **Step 1: Write the failing test (adicionar ao arquivo da Task 1)**

Adicionar estes casos ao final de `tests/Feature/Admin/Auditoria/CarimboContextoTest.php`:

```php
it('carimba empresa_id/filial_id do contexto ativo em toda atividade', function (): void {
    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);
    app(\HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);

    activity('test')->log('com contexto');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBe($empresa->id);
});

it('deixa empresa_id nulo quando não há contexto ativo', function (): void {
    app(\HT2ML\Core\Support\Tenancy\TenantContext::class)->limpar();

    activity('test')->log('sem contexto');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBeNull();
});

it('preserva empresa_id já setado explicitamente (não sobrescreve)', function (): void {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    app(\HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($a->id);

    activity('test')->tap(function (\HT2ML\Core\Models\Activity $activity) use ($b): void {
        $activity->empresa_id = $b->id;
    })->log('explícito');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBe($b->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='CarimboContextoTest'`
Expected: FAIL — sem o listener, `empresa_id` fica nulo mesmo com contexto.

- [ ] **Step 3: Criar o invokable**

`app/Support/Audit/CarimbarContextoNaAtividade.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Audit;

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use HT2ML\Core\Support\Tenancy\TenantContext;

/**
 * Carimba o contexto ambiente da requisição em cada atividade no momento do
 * `creating`: empresa/filial ativas (tenant) e, durante uma personificação, quem
 * está por trás (impersonado_por). Ponto único de "contexto → activity_log".
 */
final class CarimbarContextoNaAtividade
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly ImpersonationContext $impersonation,
    ) {}

    public function __invoke(Activity $activity): void
    {
        // ??= respeita um empresa_id/filial_id já setado explicitamente pela ação.
        $activity->empresa_id ??= $this->tenant->empresaAtivaId();
        $activity->filial_id ??= $this->tenant->filialAtivaId();

        if (! $this->impersonation->ativo()) {
            return;
        }

        $originalId = $this->impersonation->originalId();

        if ($originalId === null) {
            return;
        }

        $original = AdminUser::find($originalId);

        $activity->properties = collect($activity->properties ?? [])
            ->put('impersonado_por', ['id' => $originalId, 'nome' => $original?->nome]);
    }
}
```

- [ ] **Step 4: Registrar e remover o closure inline**

Em `app/Providers/AppServiceProvider.php`:

1. Remover os imports `use HT2ML\Core\Support\Impersonation\ImpersonationContext;` e `use HT2ML\Core\Models\AdminUser;` **se** ficarem sem uso após a remoção do closure (o Pint cuidará disso; confira que `AdminUser` ainda é usado pelo `Gate::policy`/`Gate::before` — provavelmente sim, então mantenha-o).
2. Adicionar o import `use HT2ML\Core\Support\Audit\CarimbarContextoNaAtividade;` e manter `use Spatie\Activitylog\Models\Activity;` (o `Activity::creating` aceita o model base; o invokable tipa `HT2ML\Core\Models\Activity`, que é o instanciado em runtime).
3. Substituir TODO o bloco `Activity::creating(function (Activity $activity): void { ... impersonado_por ... });` por:

```php
        Activity::creating(app(CarimbarContextoNaAtividade::class));
```

- [ ] **Step 5: Run test to verify it passes**

Run: `ddev artisan test --filter='CarimboContextoTest'`
Expected: PASS

- [ ] **Step 6: Regressão da impersonation**

Run: `ddev artisan test --filter='ImpersonationAuditoriaTest'`
Expected: PASS (o `impersonado_por` continua sendo gravado pelo invokable).

- [ ] **Step 7: Commit**

```bash
git add app/Support/Audit/CarimbarContextoNaAtividade.php app/Providers/AppServiceProvider.php tests/Feature/Admin/Auditoria/CarimboContextoTest.php
git commit -m "feat(admin): carimba empresa/filial em toda atividade"
```

---

## Task 3: Permissão `auditoria.todas-empresas`

**Files:**

- Modify: `config/access.php` (módulo `ModuloAcesso::Auditoria`)
- Test: `tests/Feature/Admin/Auditoria/AuditoriaIsolamentoTest.php` (parte 1)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('publica a permissão auditoria.todas-empresas via access:sync', function (): void {
    Artisan::call('access:sync');

    expect(Permission::where('name', 'auditoria.todas-empresas')->where('guard_name', 'admin')->exists())
        ->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaIsolamentoTest'`
Expected: FAIL — permissão inexistente.

- [ ] **Step 3: Adicionar ao catálogo**

Em `config/access.php`, no array `ModuloAcesso::Auditoria->value => [ ... ]`, após `auditoria.visualizar`:

```php
            'auditoria.todas-empresas' => [
                'label' => 'Ver auditoria de todas as empresas',
                'descricao' => 'Consultar a auditoria sem isolamento por empresa (visão cross-empresa).',
            ],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter='AuditoriaIsolamentoTest'`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add config/access.php tests/Feature/Admin/Auditoria/AuditoriaIsolamentoTest.php
git commit -m "feat(admin): permissão auditoria.todas-empresas no catálogo"
```

---

## Task 4: Serviço `AuditoriaSeguranca`

**Files:**

- Create: `app/Services/Admin/AuditoriaSeguranca.php`
- Test: `tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php` (parte 1 — serviço)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('loga login bem-sucedido com causer e flag 2fa', function (): void {
    $user = criarAdminUser('u@teste.com');

    app(AuditoriaSeguranca::class)->loginBemSucedido($user, true);

    $log = Activity::query()->where('log_name', 'auth')->where('event', 'login')->firstOrFail();
    expect($log->causer_id)->toBe($user->id)
        ->and($log->getProperty('2fa'))->toBeTrue();
});

it('loga falha de login sem causer e com o e-mail', function (): void {
    app(AuditoriaSeguranca::class)->loginFalhou('alvo@teste.com');

    $log = Activity::query()->where('log_name', 'auth')->where('event', 'login-falhou')->firstOrFail();
    expect($log->causer_id)->toBeNull()
        ->and($log->getProperty('email'))->toBe('alvo@teste.com');
});

it('loga os demais eventos de autenticação', function (): void {
    $user = criarAdminUser('u@teste.com');
    $svc = app(AuditoriaSeguranca::class);

    $svc->loginBloqueado('x@teste.com');
    $svc->logout($user);
    $svc->desafio2faFalhou($user);
    $svc->senhaResetSolicitada('x@teste.com');
    $svc->senhaResetAplicada($user);

    foreach (['login-bloqueado', 'logout', '2fa-desafio-falhou', 'senha-reset-solicitado', 'senha-reset-aplicado'] as $evento) {
        expect(Activity::query()->where('log_name', 'auth')->where('event', $evento)->exists())->toBeTrue();
    }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: FAIL — serviço inexistente.

- [ ] **Step 3: Criar o serviço**

`app/Services/Admin/AuditoriaSeguranca.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Admin;

use HT2ML\Core\Models\AdminUser;

/**
 * Centraliza o registro de eventos de segurança/autenticação na trilha de
 * auditoria (log_name "auth"), com nomes de evento e descrições PT-BR padronizados.
 */
final class AuditoriaSeguranca
{
    public function loginBemSucedido(AdminUser $usuario, bool $via2fa): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('login')
            ->withProperties(['2fa' => $via2fa])
            ->log('Login realizado');
    }

    public function loginFalhou(string $email): void
    {
        activity('auth')
            ->event('login-falhou')
            ->withProperties(['email' => $email])
            ->log('Falha de login');
    }

    public function loginBloqueado(string $email): void
    {
        activity('auth')
            ->event('login-bloqueado')
            ->withProperties(['email' => $email])
            ->log('Login bloqueado por excesso de tentativas');
    }

    public function logout(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('logout')
            ->log('Logout realizado');
    }

    public function desafio2faFalhou(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('2fa-desafio-falhou')
            ->log('Falha no desafio de verificação em duas etapas');
    }

    public function senhaResetSolicitada(string $email): void
    {
        activity('auth')
            ->event('senha-reset-solicitado')
            ->withProperties(['email' => $email])
            ->log('Redefinição de senha solicitada');
    }

    public function senhaResetAplicada(AdminUser $usuario): void
    {
        activity('auth')
            ->causedBy($usuario)
            ->event('senha-reset-aplicado')
            ->log('Senha redefinida');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Admin/AuditoriaSeguranca.php tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php
git commit -m "feat(admin): serviço de auditoria de eventos de segurança"
```

---

## Task 5: Eventos no Login (sucesso/falha/bloqueio)

**Files:**

- Modify: `app/Livewire/Admin/Auth/Login.php`
- Test: `tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php` (adicionar casos de integração)

- [ ] **Step 1: Write the failing test (adicionar ao arquivo da Task 4)**

```php
use App\Livewire\Admin\Auth\Login;
use Livewire\Livewire;

it('registra falha de login pela tela', function (): void {
    criarAdminUser('real@teste.com'); // senha "password"

    Livewire::test(Login::class)
        ->set('email', 'real@teste.com')
        ->set('password', 'errada')
        ->call('authenticate');

    expect(Activity::query()->where('event', 'login-falhou')->where('properties->email', 'real@teste.com')->exists())
        ->toBeTrue();
});

it('registra login bem-sucedido (sem 2FA) pela tela', function (): void {
    $user = criarAdminUser('real@teste.com');

    Livewire::test(Login::class)
        ->set('email', 'real@teste.com')
        ->set('password', 'password')
        ->call('authenticate');

    expect(Activity::query()->where('event', 'login')->where('causer_id', $user->id)->exists())
        ->toBeTrue();
});
```

> Nota: `criarAdminUser` cria com senha `password` (Hash). O `mount()` do Login pré-preenche credenciais só em ambiente local; nos testes o ambiente é `testing`, então os `set(...)` valem.

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: FAIL — os eventos de login não são registrados ainda.

- [ ] **Step 3: Instrumentar o Login**

Em `app/Livewire/Admin/Auth/Login.php`, adicionar o import:

```php
use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
```

No método `authenticate()`:

a) No bloqueio por rate-limit, antes do `throw`:

```php
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            app(AuditoriaSeguranca::class)->loginBloqueado($this->email);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($chave)]),
            ]);
        }
```

b) Na falha de credenciais, após `RateLimiter::hit(...)`:

```php
        if (! Auth::guard('admin')->validate(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($chave, 60);
            app(AuditoriaSeguranca::class)->loginFalhou($this->email);
            $this->addError('email', __('auth.failed'));

            return;
        }
```

c) No sucesso sem-2FA, logo após `Auth::guard('admin')->login($usuario, $this->remember);`:

```php
        Auth::guard('admin')->login($usuario, $this->remember);
        app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, false);

        session()->regenerate();
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auth/Login.php tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php
git commit -m "feat(admin): audita login (sucesso, falha, bloqueio)"
```

---

## Task 6: Eventos no desafio 2FA

**Files:**

- Modify: `app/Livewire/Admin/Auth/TwoFactorChallenge.php`
- Test: `tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php` (adicionar casos)

- [ ] **Step 1: Write the failing test (adicionar ao arquivo)**

```php
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;

it('registra falha no desafio 2FA', function (): void {
    $user = criarAdminUser('u@teste.com');
    $secret = app(TwoFactorService::class)->gerarSecret();
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

    session(['2fa.pending' => ['id' => $user->id, 'remember' => false]]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', '000000')
        ->call('verificar');

    expect(Activity::query()->where('event', '2fa-desafio-falhou')->where('causer_id', $user->id)->exists())
        ->toBeTrue();
});
```

> Nota: `TwoFactorService::gerarSecret(): string` é o método correto (confirmado). O usuário precisa ter `two_factor_secret` + `two_factor_confirmed_at` para `hasTwoFactorEnabled()` ser true; o código `'000000'` é inválido para o secret gerado.

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: FAIL — falha de 2FA não registrada.

- [ ] **Step 3: Instrumentar o TwoFactorChallenge**

Em `app/Livewire/Admin/Auth/TwoFactorChallenge.php`, adicionar o import:

```php
use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
```

No método `verificar()`:

a) No bloqueio por rate-limit, antes do `throw`:

```php
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            app(AuditoriaSeguranca::class)->loginBloqueado((string) ($pendente['id'] ?? 'desconhecido'));

            throw ValidationException::withMessages([
                'codigo' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($chave)]),
            ]);
        }
```

b) Na falha de código (quando `codigoConfere` retorna false), antes do `return`:

```php
        if (! $this->codigoConfere($service, $usuario)) {
            RateLimiter::hit($chave, 60);
            app(AuditoriaSeguranca::class)->desafio2faFalhou($usuario);

            return;
        }
```

c) No sucesso, após `Auth::guard('admin')->login($usuario, ...)`:

```php
        Auth::guard('admin')->login($usuario, (bool) ($pendente['remember'] ?? false));
        app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, true);
        session()->forget('2fa.pending');
        session()->regenerate();
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auth/TwoFactorChallenge.php tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php
git commit -m "feat(admin): audita desafio 2FA (falha, bloqueio, sucesso)"
```

---

## Task 7: Eventos de logout e reset de senha

**Files:**

- Modify: `app/Http/Controllers/Admin/Auth/LogoutController.php`
- Modify: `app/Livewire/Admin/Auth/ForgotPassword.php`
- Modify: `app/Livewire/Admin/Auth/ResetPassword.php`
- Test: `tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php` (adicionar casos)

- [ ] **Step 1: Write the failing test (adicionar ao arquivo)**

```php
use App\Livewire\Admin\Auth\ForgotPassword;

it('registra logout genuíno (não durante personificação)', function (): void {
    $user = criarAdminUser('u@teste.com');
    $this->actingAs($user, 'admin');

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    expect(Activity::query()->where('event', 'logout')->where('causer_id', $user->id)->exists())
        ->toBeTrue();
});

it('não duplica logout durante personificação (só encerrada)', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->post(route('admin.logout'));

    expect(Activity::query()->where('log_name', 'auth')->where('event', 'logout')->exists())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'impersonation')->where('event', 'encerrada')->exists())->toBeTrue();
});

it('registra solicitação de reset de senha', function (): void {
    criarAdminUser('u@teste.com');

    Livewire::test(ForgotPassword::class)
        ->set('email', 'u@teste.com')
        ->call('sendLink');

    expect(Activity::query()->where('event', 'senha-reset-solicitado')->where('properties->email', 'u@teste.com')->exists())
        ->toBeTrue();
});
```

> O reset APLICADO (`ResetPassword`) é melhor coberto por um teste com token real do broker; aqui garantimos o caminho de log via a instrumentação do Step 3c, e o `AuditoriaSegurancaTest` do serviço já cobre `senhaResetAplicada`. (Opcional: teste de integração do broker se desejado.)

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Expected: FAIL — logout/solicitação de reset não registrados.

- [ ] **Step 3: Instrumentar os três pontos**

a) `app/Http/Controllers/Admin/Auth/LogoutController.php` — adicionar import e logar o logout genuíno no ramo NÃO-impersonation. Estrutura final do `__invoke`:

```php
    public function __invoke(Request $request, ImpersonationContext $context): RedirectResponse
    {
        $usuario = Auth::guard('admin')->user();

        if ($context->ativo()) {
            $originalId = $context->originalId();
            $original = $originalId !== null ? AdminUser::find($originalId) : null;
            $context->encerrar();

            activity('impersonation')
                ->causedBy($original)
                ->performedOn($usuario)
                ->event('encerrada')
                ->log('Personificação encerrada (logout)');
        } elseif ($usuario instanceof AdminUser) {
            app(\HT2ML\Core\Services\Admin\AuditoriaSeguranca::class)->logout($usuario);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
```

b) `app/Livewire/Admin/Auth/ForgotPassword.php` — adicionar import `use HT2ML\Core\Services\Admin\AuditoriaSeguranca;` e logar quando o link é enviado:

```php
        if ($status === Password::RESET_LINK_SENT) {
            app(AuditoriaSeguranca::class)->senhaResetSolicitada($this->email);
            session()->flash('status', __($status));
            $this->email = '';
        } else {
            $this->addError('email', __($status));
        }
```

c) `app/Livewire/Admin/Auth/ResetPassword.php` — adicionar import `use HT2ML\Core\Services\Admin\AuditoriaSeguranca;` e logar a aplicação no sucesso (o usuário é localizado por e-mail):

```php
        if ($status === Password::PASSWORD_RESET) {
            $usuario = \HT2ML\Core\Models\AdminUser::where('email', $this->email)->first();

            if ($usuario !== null) {
                app(AuditoriaSeguranca::class)->senhaResetAplicada($usuario);
            }

            session()->flash('success', __($status));
            $this->redirect(route('admin.login'), navigate: true);
        } else {
            $this->addError('email', __($status));
        }
```

- [ ] **Step 4: Run test to verify it passes + regressão**

Run: `ddev artisan test --filter='AuditoriaSegurancaTest'`
Run: `ddev artisan test --filter='LogoutDuranteImpersonationTest'`
Expected: PASS nos dois (o teste de impersonation-logout continua verde).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/Auth/LogoutController.php app/Livewire/Admin/Auth/ForgotPassword.php app/Livewire/Admin/Auth/ResetPassword.php tests/Feature/Admin/Auditoria/AuditoriaSegurancaTest.php
git commit -m "feat(admin): audita logout e reset de senha"
```

---

## Task 8: Isolamento + coluna/filtro de empresa na tela

**Files:**

- Modify: `app/Livewire/Admin/Auditoria/AuditoriaTable.php`
- Test: `tests/Feature/Admin/Auditoria/AuditoriaIsolamentoTest.php` (adicionar casos)

- [ ] **Step 1: Write the failing test (adicionar ao arquivo da Task 3)**

```php
use App\Livewire\Admin\Auditoria\AuditoriaTable;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Livewire\Livewire;

/** Cria 3 atividades-âncora: empresa A, empresa B e sem empresa. */
function semearAuditoria(): array
{
    $a = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
    $ctx = app(TenantContext::class);

    $ctx->definirEmpresa($a->id);
    activity('test')->log('evento-empresa-A');
    $ctx->definirEmpresa($b->id);
    activity('test')->log('evento-empresa-B');
    $ctx->limpar();
    activity('test')->log('evento-sem-empresa');

    return [$a, $b];
}

it('isola: gestor vê só a empresa ativa, não outra nem sem-empresa', function (): void {
    [$a] = semearAuditoria();

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor', 50)->givePermissionTo(
        Spatie\Permission\Models\Permission::findOrCreate('auditoria.visualizar', 'admin')
    );
    $gestor->assignRole('gestor');

    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => $a->id]); // empresa ativa = A

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertDontSee('evento-empresa-B')
        ->assertDontSee('evento-sem-empresa');
});

it('super-admin vê tudo (inclusive sem-empresa)', function (): void {
    semearAuditoria();

    $super = criarAdminUser('super@teste.com');
    criarRoleAdmin('super-admin', 100);
    $super->assignRole('super-admin');

    $this->actingAs($super, 'admin');

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertSee('evento-empresa-B')
        ->assertSee('evento-sem-empresa');
});

it('portador de auditoria.todas-empresas vê tudo', function (): void {
    semearAuditoria();

    $auditor = criarAdminUser('auditor@teste.com');
    criarRoleAdmin('auditor', 30)->givePermissionTo([
        Spatie\Permission\Models\Permission::findOrCreate('auditoria.visualizar', 'admin'),
        Spatie\Permission\Models\Permission::findOrCreate('auditoria.todas-empresas', 'admin'),
    ]);
    $auditor->assignRole('auditor');

    $this->actingAs($auditor, 'admin');
    session(['tenant.empresa_id' => null]);

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertSee('evento-empresa-B')
        ->assertSee('evento-sem-empresa');
});

it('isolado sem empresa ativa não vê nada', function (): void {
    semearAuditoria();

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor', 50)->givePermissionTo(
        Spatie\Permission\Models\Permission::findOrCreate('auditoria.visualizar', 'admin')
    );
    $gestor->assignRole('gestor');

    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => null]); // sem empresa ativa

    Livewire::test(AuditoriaTable::class)
        ->assertDontSee('evento-empresa-A')
        ->assertDontSee('evento-empresa-B')
        ->assertDontSee('evento-sem-empresa');
});
```

> `beforeEach` do arquivo já roda `Artisan::call('access:sync')` (da Task 3) — garanta que está no topo do arquivo para que `auditoria.*` existam ao atribuir às roles.

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter='AuditoriaIsolamentoTest'`
Expected: FAIL — sem isolamento, o gestor vê eventos de outras empresas/sem-empresa.

- [ ] **Step 3: Aplicar isolamento + coluna/filtro no AuditoriaTable**

Em `app/Livewire/Admin/Auditoria/AuditoriaTable.php`:

a) Trocar o import do model para o custom:

```php
use HT2ML\Core\Models\Activity;
```

(remover `use Spatie\Activitylog\Models\Activity;`)

b) Adicionar imports:

```php
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
```

c) `datasource()` passa a isolar:

```php
    /**
     * @return Builder<Activity>
     */
    public function datasource(): Builder
    {
        $query = Activity::query()->with(['causer', 'subject', 'empresa']);

        if (! $this->podeVerTodasEmpresas()) {
            $empresaId = app(TenantContext::class)->empresaAtivaId();

            $empresaId === null
                ? $query->whereRaw('1 = 0')
                : $query->where('empresa_id', $empresaId);
        }

        return $query;
    }

    private function podeVerTodasEmpresas(): bool
    {
        return Auth::guard('admin')->user()?->can('auditoria.todas-empresas') ?? false;
    }
```

d) Adicionar o campo e a coluna de empresa. No `fields()`, adicionar:

```php
            ->add('empresa', fn (Activity $a): string => $a->empresa?->getAttribute('nome') ?? '—')
```

No `columns()`, adicionar antes da coluna "Descrição":

```php
            Column::make('Empresa', 'empresa'),
```

e) (Opcional, recomendado) Filtro por empresa só para privilegiados. No `filters()`, ao final do array, condicionar:

```php
        $filtros = [
            Filter::select('log_name')->dataSource($this->opcoes('log_name'))->optionValue('valor')->optionLabel('valor'),
            Filter::select('event')->dataSource($this->opcoes('event'))->optionValue('valor')->optionLabel('valor'),
            Filter::datepicker('created_at_formatted', 'created_at'),
        ];

        if ($this->podeVerTodasEmpresas()) {
            $filtros[] = Filter::select('empresa', 'empresa_id')
                ->dataSource(Empresa::query()->orderBy('nome')->get(['id', 'nome'])->all())
                ->optionValue('id')
                ->optionLabel('nome');
        }

        return $filtros;
```

(troque o `return [...]` atual do `filters()` por esta construção.)

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter='AuditoriaIsolamentoTest'`
Expected: PASS (4 casos de isolamento + o de catálogo da Task 3)

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auditoria/AuditoriaTable.php tests/Feature/Admin/Auditoria/AuditoriaIsolamentoTest.php
git commit -m "feat(admin): isola auditoria por empresa + coluna/filtro de empresa"
```

---

## Task 9: Portão de qualidade + sincronização

**Files:** nenhum novo (verificação).

- [ ] **Step 1: Sincronizar permissões e rodar a suíte completa**

Run:

```bash
ddev artisan access:sync
ddev artisan test --filter='Auditoria'
ddev artisan test
```

Expected: toda a suíte verde (incluindo Impersonation e os testes pré-existentes de auditoria/auth).

- [ ] **Step 2: Pint + PHPStan**

Run:

```bash
ddev exec ./vendor/bin/pint --dirty
ddev exec ./vendor/bin/phpstan analyse
```

Expected: Pint sem pendências; PHPStan nível 6 sem erros.

- [ ] **Step 3: Verificação visual (manual, quando o router voltar)**

1. Login/logout/erro de senha → conferir os eventos em `/admin/auditoria` com a coluna Empresa.
2. Como gestor (com `auditoria.visualizar`, empresa ativa definida): vê só a auditoria da própria empresa.
3. Como super-admin / `auditoria.todas-empresas`: vê tudo e usa o filtro de empresa.

- [ ] **Step 4: Commit final (se houver ajuste de formatação)**

```bash
git add -A -- app config tests
git commit -m "chore(admin): ajustes finais da auditoria tenant-aware"
```

(NÃO commitar `yarn.lock` nem `.workflows/`.)

---

## Self-review (cobertura do spec)

- Decisão 1 (colunas reais + Activity custom) → Task 1.
- Decisão 2 (isolamento + coluna/filtro sempre presentes) → Task 8.
- Decisão 3 (cross-empresa por super-admin/`auditoria.todas-empresas`) → Tasks 3, 8 (`->can('auditoria.todas-empresas')` cobre super-admin via bypass do Gate).
- Decisão 4 (ciclo de autenticação completo) → Tasks 4, 5, 6, 7.
- Decisão 5 (serviço + logging explícito) → Task 4 + wiring Tasks 5-7.
- Carimbo de tenant em toda atividade → Task 2 (+ regressão impersonation).
- Decisão 6 (retenção deferida) → fora de escopo (nenhuma task), conforme spec.
