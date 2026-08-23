# Impersonation de usuário — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir que um admin autorizado "entre como" outro usuário admin (act-as) para suporte/diagnóstico — com banner persistente, saída manual, teto de tempo, trilha de auditoria e guardas de hierarquia + tenant.

**Architecture:** Mecânica sob medida sobre o guard `admin`, espelhando o padrão de "contexto ativo" (`TenantContext`). Estado na sessão (`ImpersonationContext`), operações em Actions API-ready, entrada via modal Livewire na tabela de Usuários, saída via rota POST + banner Blade global, expiração via middleware, atribuição de auditoria via listener `Activity::creating`. Sem dependência nova.

**Tech Stack:** Laravel 13, PHP 8.4, Livewire 4, PowerGrid, spatie/laravel-permission, spatie/laravel-activitylog, spatie/laravel-settings, Pest 4, Pint, PHPStan nível 6.

**Spec:** `docs/superpowers/specs/2026-06-05-impersonation-fase-b-design.md`

**Branch:** `feat/impersonation`

**Comandos de teste/qualidade** (rodar via DDEV; se o ambiente estiver fora, executar quando voltar):

- Testes: `ddev artisan test --filter='<nome>'`
- Pint: `ddev exec ./vendor/bin/pint --dirty`
- PHPStan: `ddev exec ./vendor/bin/phpstan analyse`

> Convenções obrigatórias (CLAUDE.md): `declare(strict_types=1)` em todo PHP; type hints e return types sempre; Actions nunca recebem `Request`; mensagens de usuário em PT-BR. Todas as roles/permissions usam o guard `admin`.
>
> Nota de testes: os snippets usam o helper `criarAdminUser()` (de `tests/Pest.php`). O hook de pre-commit (lint-staged) roda `pint --dirty`, que remove imports não usados automaticamente — não se preocupe se um `use HT2ML\Core\Models\AdminUser;` ficar redundante em algum teste. O dashboard (`admin.dashboard`) é aberto a qualquer admin autenticado (sem permissão), por isso é usado como rota-alvo nos testes de middleware/banner.

---

## Estrutura de arquivos

**Criar:**

- `app/Support/Impersonation/ImpersonationContext.php` — estado da personificação na sessão.
- `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php` — inicia (valida + swap + auditoria).
- `app/Actions/Admin/Impersonation/EncerrarImpersonationAction.php` — encerra (restaura original).
- `app/Http/Middleware/EncerrarImpersonationExpirada.php` — reverte ao expirar o teto.
- `app/Http/Controllers/Admin/ImpersonationController.php` — rota POST de saída (thin).
- `app/Livewire/Admin/Impersonation/IniciarImpersonation.php` (+ `resources/views/livewire/admin/impersonation/iniciar-impersonation.blade.php`) — modal de entrada.
- `resources/views/components/admin/impersonation-banner.blade.php` — banner global.
- `tests/Feature/Admin/Impersonation/*` — suíte.

**Modificar:**

- `config/access.php` — permissão `usuarios.impersonar`.
- `app/Settings/SegurancaSettings.php` + `database/settings/<nova>_add_impersonation_timeout.php` — `impersonation_timeout_minutos`.
- `app/Policies/AdminUserPolicy.php` — método `impersonate`.
- `app/Providers/AppServiceProvider.php` — singleton + listener `Activity::creating`.
- `app/Http/Middleware/EnsureTwoFactorEnabled.php` — pular exigência de 2FA quando personificando.
- `app/Livewire/Admin/Conta/SegurancaConta.php` — travas nas ações sensíveis.
- `app/Http/Controllers/Admin/Auth/LogoutController.php` — auditar encerramento no logout.
- `routes/admin.php` — rota de saída + middleware de expiração na cadeia autenticada.
- `resources/views/components/admin/layout.blade.php` — banner no topo do `.wrapper`.
- `app/Livewire/Admin/Usuarios/UsuariosTable.php` — ação de linha "Entrar como".
- `resources/views/livewire/admin/usuarios/index-usuarios.blade.php` — montar o modal.
- `tests/Pest.php` — helper `criarRoleAdmin()`.

---

## Task 1: Permissão `usuarios.impersonar` no catálogo

**Files:**

- Modify: `config/access.php:110-127` (bloco `ModuloAcesso::Usuarios`)
- Test: `tests/Feature/Admin/Impersonation/CatalogoPermissaoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('publica a permissão usuarios.impersonar via access:sync', function (): void {
    Artisan::call('access:sync');

    expect(Permission::where('name', 'usuarios.impersonar')->where('guard_name', 'admin')->exists())
        ->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=CatalogoPermissaoTest`
Expected: FAIL — a permissão não existe no catálogo.

- [ ] **Step 3: Adicionar a permissão ao catálogo**

Em `config/access.php`, dentro do array `ModuloAcesso::Usuarios->value => [ ... ]`, após `usuarios.deletar`:

```php
            'usuarios.impersonar' => [
                'label' => 'Personificar usuários',
                'descricao' => 'Entrar como outro usuário (act-as) para suporte e diagnóstico.',
            ],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=CatalogoPermissaoTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add config/access.php tests/Feature/Admin/Impersonation/CatalogoPermissaoTest.php
git commit -m "feat(admin): permissão usuarios.impersonar no catálogo"
```

---

## Task 2: Setting `impersonation_timeout_minutos`

**Files:**

- Modify: `app/Settings/SegurancaSettings.php:31` (após `dias_retencao_logs`)
- Create: `database/settings/2026_06_05_000001_add_impersonation_timeout.php`
- Test: `tests/Feature/Admin/Impersonation/ImpersonationSettingTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expõe impersonation_timeout_minutos com default 30', function (): void {
    expect(app(SegurancaSettings::class)->impersonation_timeout_minutos)->toBe(30);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=ImpersonationSettingTest`
Expected: FAIL — `MissingSettings` ou propriedade inexistente.

- [ ] **Step 3: Adicionar a propriedade e a migration de settings**

Em `app/Settings/SegurancaSettings.php`, após `public int $dias_retencao_logs;`:

```php
    public int $impersonation_timeout_minutos;
```

Criar `database/settings/2026_06_05_000001_add_impersonation_timeout.php`:

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seguranca.impersonation_timeout_minutos', 30);
    }
};
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan migrate --force && ddev artisan test --filter=ImpersonationSettingTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Settings/SegurancaSettings.php database/settings/2026_06_05_000001_add_impersonation_timeout.php tests/Feature/Admin/Impersonation/ImpersonationSettingTest.php
git commit -m "feat(admin): setting de teto de tempo da personificação"
```

---

## Task 3: `ImpersonationContext` (estado na sessão)

**Files:**

- Create: `app/Support/Impersonation/ImpersonationContext.php`
- Modify: `app/Providers/AppServiceProvider.php:27` (register singleton)
- Test: `tests/Feature/Admin/Impersonation/ImpersonationContextTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Auth\Access\AuthorizationException;

it('guarda, expõe, expira e limpa o estado da personificação', function (): void {
    $ctx = app(ImpersonationContext::class);

    expect($ctx->ativo())->toBeFalse();

    $ctx->iniciar(7, 'suporte ao cliente');

    expect($ctx->ativo())->toBeTrue()
        ->and($ctx->originalId())->toBe(7)
        ->and($ctx->motivo())->toBe('suporte ao cliente')
        ->and($ctx->expirado(30))->toBeFalse();

    $ctx->encerrar();

    expect($ctx->ativo())->toBeFalse()
        ->and($ctx->originalId())->toBeNull();
});

it('garantirNaoPersonificando lança quando ativo e é no-op quando inativo', function (): void {
    $ctx = app(ImpersonationContext::class);

    $ctx->garantirNaoPersonificando(); // não lança

    $ctx->iniciar(1, 'motivo qualquer');

    expect(fn () => $ctx->garantirNaoPersonificando())->toThrow(AuthorizationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=ImpersonationContextTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Criar a classe**

`app/Support/Impersonation/ImpersonationContext.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support\Impersonation;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Estado da personificação (act-as) na sessão. Espelha o TenantContext: é a fonte
 * única do "estou personificando" consumida pelo banner, middleware de expiração,
 * travas de ações sensíveis e atribuição de auditoria.
 */
final class ImpersonationContext
{
    private const ORIGINAL = 'impersonate.original_id';

    private const STARTED = 'impersonate.started_at';

    private const MOTIVO = 'impersonate.motivo';

    public function iniciar(int $originalId, string $motivo): void
    {
        session([
            self::ORIGINAL => $originalId,
            self::STARTED => time(),
            self::MOTIVO => $motivo,
        ]);
    }

    public function ativo(): bool
    {
        return is_int(session(self::ORIGINAL));
    }

    public function originalId(): ?int
    {
        $id = session(self::ORIGINAL);

        return is_int($id) ? $id : null;
    }

    public function motivo(): ?string
    {
        $motivo = session(self::MOTIVO);

        return is_string($motivo) ? $motivo : null;
    }

    /**
     * Momento de início como timestamp UNIX (segundos), ou null se inativo.
     */
    public function iniciadoEm(): ?int
    {
        $ts = session(self::STARTED);

        return is_int($ts) ? $ts : null;
    }

    public function expirado(int $minutos): bool
    {
        $iniciadoEm = $this->iniciadoEm();

        if ($iniciadoEm === null) {
            return false;
        }

        return (time() - $iniciadoEm) >= $minutos * 60;
    }

    public function encerrar(): void
    {
        session()->forget([self::ORIGINAL, self::STARTED, self::MOTIVO]);
    }

    /**
     * Barreira para ações sensíveis: recusa quando há personificação ativa.
     */
    public function garantirNaoPersonificando(): void
    {
        if ($this->ativo()) {
            throw new AuthorizationException('Ação indisponível durante a personificação.');
        }
    }
}
```

Em `app/Providers/AppServiceProvider.php`, dentro de `register()`, após o singleton do `TenantContext`:

```php
        $this->app->singleton(\HT2ML\Core\Support\Impersonation\ImpersonationContext::class);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=ImpersonationContextTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Support/Impersonation/ImpersonationContext.php app/Providers/AppServiceProvider.php tests/Feature/Admin/Impersonation/ImpersonationContextTest.php
git commit -m "feat(admin): contexto de personificação na sessão"
```

---

## Task 4: Policy `impersonate` + helper de testes

**Files:**

- Modify: `app/Policies/AdminUserPolicy.php:44` (novo método)
- Modify: `tests/Pest.php:45` (helper `criarRoleAdmin`)
- Test: `tests/Feature/Admin/Impersonation/ImpersonatePolicyTest.php`

- [ ] **Step 1: Adicionar o helper de testes**

Em `tests/Pest.php`, após a função `criarAdminUser`:

```php
/**
 * Cria (ou recupera) uma role do guard admin com um nível hierárquico.
 */
function criarRoleAdmin(string $name, int $nivel): Spatie\Permission\Models\Role
{
    $role = Spatie\Permission\Models\Role::findOrCreate($name, 'admin');
    $role->forceFill(['nivel' => $nivel])->save();

    return $role;
}
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
});

it('permite personificar alvo de nível inferior quando tem a permissão', function (): void {
    $gestorRole = criarRoleAdmin('gestor', 50);
    $gestorRole->givePermissionTo('usuarios.impersonar');
    criarRoleAdmin('operador', 10);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    expect(Gate::forUser($ator)->allows('impersonate', $alvo))->toBeTrue();
});

it('nega sem a permissão, ou contra nível igual/superior', function (): void {
    criarRoleAdmin('gestor', 50);
    criarRoleAdmin('operador', 10);

    $semPermissao = criarAdminUser('sem@teste.com');
    $semPermissao->assignRole('gestor'); // sem usuarios.impersonar
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    expect(Gate::forUser($semPermissao)->allows('impersonate', $alvo))->toBeFalse();

    // mesmo com a permissão, não personifica um par (nível igual)
    $gestorRole = criarRoleAdmin('gestor', 50)->givePermissionTo('usuarios.impersonar');
    $par = criarAdminUser('par@teste.com');
    $par->assignRole('gestor');

    expect(Gate::forUser($semPermissao)->allows('impersonate', $par))->toBeFalse();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `ddev artisan test --filter=ImpersonatePolicyTest`
Expected: FAIL — método `impersonate` não existe na policy.

- [ ] **Step 4: Adicionar o método à policy**

Em `app/Policies/AdminUserPolicy.php`, após `gerenciarAcessos`:

```php
    public function impersonate(AdminUser $auth, AdminUser $usuario): bool
    {
        return $auth->can('usuarios.impersonar') && $this->hierarchy->podeGerir($auth, $usuario);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `ddev artisan test --filter=ImpersonatePolicyTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Policies/AdminUserPolicy.php tests/Pest.php tests/Feature/Admin/Impersonation/ImpersonatePolicyTest.php
git commit -m "feat(admin): policy de personificação (permissão + hierarquia)"
```

---

## Task 5: `IniciarImpersonationAction`

**Files:**

- Create: `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php`
- Test: `tests/Feature/Admin/Impersonation/IniciarImpersonationActionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\Admin\Impersonation\IniciarImpersonationAction;
use App\Exceptions\AccessException;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('gestor', 50)->givePermissionTo('usuarios.impersonar');
    criarRoleAdmin('operador', 10);
});

it('super-admin personifica: troca o usuário autenticado e ativa o contexto', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);

    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigar bug do cliente');

    expect(Auth::guard('admin')->id())->toBe($alvo->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeTrue()
        ->and(app(ImpersonationContext::class)->originalId())->toBe($ator->id);
});

it('recusa auto-personificação, alvo inativo, super-admin e hierarquia', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $superAlvo = criarAdminUser('super@teste.com');
    $superAlvo->assignRole('super-admin');
    $inativo = criarAdminUser('inativo@teste.com', ativo: false);
    $inativo->assignRole('operador');

    $action = app(IniciarImpersonationAction::class);

    expect(fn () => $action->execute($ator, $ator, 'x'))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($ator, $inativo, 'x'))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($ator, $superAlvo, 'x'))->toThrow(AccessException::class);
});

it('gestor só personifica alvo da empresa ativa dele', function (): void {
    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $ator->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);
    $ator->update(['empresa_ativa_id' => $empresa->id]);

    $alvoMesmaEmpresa = criarAdminUser('mesma@teste.com');
    $alvoMesmaEmpresa->assignRole('operador');
    $alvoMesmaEmpresa->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);

    $alvoForaDaEmpresa = criarAdminUser('fora@teste.com');
    $alvoForaDaEmpresa->assignRole('operador');

    $action = app(IniciarImpersonationAction::class);

    expect(fn () => $action->execute($ator, $alvoForaDaEmpresa, 'x'))->toThrow(AccessException::class);

    $action->execute($ator, $alvoMesmaEmpresa, 'suporte na empresa');
    expect(app(ImpersonationContext::class)->ativo())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=IniciarImpersonationActionTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Criar a Action**

`app/Actions/Admin/Impersonation/IniciarImpersonationAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin\Impersonation;

use App\Exceptions\AccessException;
use HT2ML\Core\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Services\Admin\HierarchyResolver;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Support\Facades\Auth;

/**
 * Inicia uma personificação (act-as). Revalida a elegibilidade no servidor
 * (defense-in-depth), registra o evento de auditoria com o ator real como causer,
 * grava o contexto e troca o usuário autenticado para o alvo.
 */
final class IniciarImpersonationAction
{
    public function __construct(
        private readonly HierarchyResolver $hierarchy,
        private readonly ImpersonationContext $context,
        private readonly AccessResolver $accessResolver,
    ) {}

    public function execute(AdminUser $ator, AdminUser $alvo, string $motivo): void
    {
        $this->garantirElegivel($ator, $alvo);

        // Logado ANTES de ativar o contexto: causer = ator real e o listener de
        // auditoria (Activity::creating) não marca este evento como personificação.
        activity('impersonation')
            ->causedBy($ator)
            ->performedOn($alvo)
            ->event('iniciada')
            ->withProperties(['motivo' => $motivo])
            ->log('Personificação iniciada');

        $this->context->iniciar((int) $ator->getKey(), $motivo);
        Auth::guard('admin')->login($alvo);
        $this->accessResolver->invalidar($alvo);
    }

    private function garantirElegivel(AdminUser $ator, AdminUser $alvo): void
    {
        if ($this->context->ativo()) {
            throw new AccessException('Encerre a personificação atual antes de iniciar outra.');
        }

        if ($ator->is($alvo)) {
            throw new AccessException('Você não pode personificar a si mesmo.');
        }

        if (! $alvo->ativo) {
            throw new AccessException('Não é possível personificar um usuário inativo.');
        }

        if ($this->ehSuperAdmin($alvo)) {
            throw new AccessException('Não é possível personificar um super-administrador.');
        }

        if (! $this->hierarchy->podeGerir($ator, $alvo)) {
            throw new AccessException('Você não tem hierarquia para personificar este usuário.');
        }

        if (! $this->ehSuperAdmin($ator) && ! $this->compartilhaEmpresaAtiva($ator, $alvo)) {
            throw new AccessException('Este usuário não pertence a uma empresa que você acessa.');
        }
    }

    private function compartilhaEmpresaAtiva(AdminUser $ator, AdminUser $alvo): bool
    {
        $empresaAtiva = $ator->empresa_ativa_id;

        return is_int($empresaAtiva) && $alvo->temAcessoAEmpresa($empresaAtiva);
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains('name', (string) config('access.super_admin_role', 'super-admin'));
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=IniciarImpersonationActionTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Admin/Impersonation/IniciarImpersonationAction.php tests/Feature/Admin/Impersonation/IniciarImpersonationActionTest.php
git commit -m "feat(admin): action de iniciar personificação"
```

---

## Task 6: `EncerrarImpersonationAction`

**Files:**

- Create: `app/Actions/Admin/Impersonation/EncerrarImpersonationAction.php`
- Test: `tests/Feature/Admin/Impersonation/EncerrarImpersonationActionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('restaura o usuário original e limpa o contexto', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    Auth::guard('admin')->login($alvo);
    app(ImpersonationContext::class)->iniciar($original->id, 'suporte');

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('faz logout completo quando o original ficou inválido', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    Auth::guard('admin')->login($alvo);
    app(ImpersonationContext::class)->iniciar($original->id, 'suporte');
    $original->update(['ativo' => false]);

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('é no-op idempotente quando não há personificação', function (): void {
    $user = criarAdminUser('u@teste.com');
    Auth::guard('admin')->login($user);

    app(EncerrarImpersonationAction::class)->execute();

    expect(Auth::guard('admin')->id())->toBe($user->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=EncerrarImpersonationActionTest`
Expected: FAIL — classe não existe.

- [ ] **Step 3: Criar a Action**

`app/Actions/Admin/Impersonation/EncerrarImpersonationAction.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\Admin\Impersonation;

use HT2ML\Core\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Support\Facades\Auth;

/**
 * Encerra a personificação: registra o evento de fim, limpa o contexto e
 * restaura o usuário original. Se o original ficou inválido (desativado/excluído),
 * faz logout completo por segurança.
 */
final class EncerrarImpersonationAction
{
    public function __construct(
        private readonly ImpersonationContext $context,
        private readonly AccessResolver $accessResolver,
    ) {}

    public function execute(): void
    {
        if (! $this->context->ativo()) {
            return;
        }

        $originalId = $this->context->originalId();
        $original = $originalId !== null ? AdminUser::find($originalId) : null;
        $alvo = Auth::guard('admin')->user();

        // Contexto encerrado ANTES de logar: o evento de fim não é marcado como
        // personificação e o causer é o ator real.
        $this->context->encerrar();

        activity('impersonation')
            ->causedBy($original ?? $alvo)
            ->performedOn($alvo)
            ->event('encerrada')
            ->log('Personificação encerrada');

        if ($original instanceof AdminUser && $original->ativo) {
            Auth::guard('admin')->login($original);
            $this->accessResolver->invalidar($original);

            return;
        }

        Auth::guard('admin')->logout();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=EncerrarImpersonationActionTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Admin/Impersonation/EncerrarImpersonationAction.php tests/Feature/Admin/Impersonation/EncerrarImpersonationActionTest.php
git commit -m "feat(admin): action de encerrar personificação"
```

---

## Task 7: Atribuição de auditoria (listener `Activity::creating`)

**Files:**

- Modify: `app/Providers/AppServiceProvider.php:60` (dentro de `boot()`)
- Test: `tests/Feature/Admin/Impersonation/ImpersonationAuditoriaTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Actions\Admin\Impersonation\IniciarImpersonationAction;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('marca ações feitas durante a personificação com impersonado_por', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigação');

    // Ação durante a personificação: edição do próprio perfil do alvo (LogsActivity).
    $alvo->update(['nome' => 'Nome Alterado']);

    $log = Activity::query()->where('log_name', 'admin_users')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->getExtraProperty('impersonado_por'))->toMatchArray(['id' => $ator->id]);
});

it('não marca o evento de início da personificação', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigação');

    $inicio = Activity::query()->where('log_name', 'impersonation')->where('event', 'iniciada')->first();

    expect($inicio)->not->toBeNull()
        ->and($inicio->causer_id)->toBe($ator->id)
        ->and($inicio->getExtraProperty('motivo'))->toBe('investigação')
        ->and($inicio->getExtraProperty('impersonado_por'))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=ImpersonationAuditoriaTest`
Expected: FAIL — `impersonado_por` ausente nas ações durante a personificação.

- [ ] **Step 3: Registrar o listener**

Em `app/Providers/AppServiceProvider.php`, adicionar o import no topo:

```php
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Spatie\Activitylog\Models\Activity;
```

E, dentro de `boot()`, após o bloco `Gate::before(...)`:

```php
        // Marca toda atividade gravada durante uma personificação com quem está
        // por trás (impersonado_por). O causer permanece o alvo (act-as).
        Activity::creating(function (Activity $activity): void {
            $context = app(ImpersonationContext::class);

            if (! $context->ativo()) {
                return;
            }

            $originalId = $context->originalId();

            if ($originalId === null) {
                return;
            }

            $original = AdminUser::find($originalId);

            $activity->properties = collect($activity->properties ?? [])
                ->put('impersonado_por', ['id' => $originalId, 'nome' => $original?->nome]);
        });
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=ImpersonationAuditoriaTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Providers/AppServiceProvider.php tests/Feature/Admin/Impersonation/ImpersonationAuditoriaTest.php
git commit -m "feat(admin): auditoria marca ações de personificação (impersonado_por)"
```

---

## Task 8: Saída — `ImpersonationController` + rota POST

**Files:**

- Create: `app/Http/Controllers/Admin/ImpersonationController.php`
- Modify: `routes/admin.php:97` (após a rota de logout)
- Test: `tests/Feature/Admin/Impersonation/SairImpersonationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('POST /admin/impersonation/sair restaura o usuário original', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->post(route('admin.impersonation.sair'))
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=SairImpersonationTest`
Expected: FAIL — rota inexistente.

- [ ] **Step 3: Criar o controller**

`app/Http/Controllers/Admin/ImpersonationController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

final class ImpersonationController extends Controller
{
    public function sair(EncerrarImpersonationAction $action): RedirectResponse
    {
        $action->execute();

        return redirect()->route('admin.dashboard');
    }
}
```

- [ ] **Step 4: Registrar a rota**

Em `routes/admin.php`, dentro do grupo autenticado, logo após a rota `logout`:

```php
    Route::post('/impersonation/sair', [App\Http\Controllers\Admin\ImpersonationController::class, 'sair'])
        ->name('impersonation.sair');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `ddev artisan test --filter=SairImpersonationTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Admin/ImpersonationController.php routes/admin.php tests/Feature/Admin/Impersonation/SairImpersonationTest.php
git commit -m "feat(admin): rota de saída da personificação"
```

---

## Task 9: Middleware de expiração

**Files:**

- Create: `app/Http/Middleware/EncerrarImpersonationExpirada.php`
- Modify: `routes/admin.php:94` (adicionar à cadeia, após `admin.auth`)
- Test: `tests/Feature/Admin/Impersonation/ExpiracaoImpersonationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Settings\SegurancaSettings;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('reverte ao original quando a personificação passa do teto de tempo', function (): void {
    $settings = app(SegurancaSettings::class);
    $settings->impersonation_timeout_minutos = 30;
    $settings->save();

    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => Carbon::now()->subMinutes(31)->timestamp,
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))->assertOk();

    expect(Auth::guard('admin')->id())->toBe($original->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse();
});

it('mantém a personificação dentro do teto', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => Carbon::now()->subMinutes(5)->timestamp,
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))->assertOk();

    expect(Auth::guard('admin')->id())->toBe($alvo->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=ExpiracaoImpersonationTest`
Expected: FAIL — sem reversão (middleware inexistente).

- [ ] **Step 3: Criar o middleware**

`app/Http/Middleware/EncerrarImpersonationExpirada.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Admin\Impersonation\EncerrarImpersonationAction;
use HT2ML\Core\Settings\SegurancaSettings;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reverte automaticamente uma personificação que passou do teto de tempo
 * (SegurancaSettings::impersonation_timeout_minutos), devolvendo o ator ao seu
 * próprio usuário. Roda logo após a autenticação.
 */
final class EncerrarImpersonationExpirada
{
    public function __construct(
        private readonly ImpersonationContext $context,
        private readonly EncerrarImpersonationAction $encerrar,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->ativo()
            && $this->context->expirado(app(SegurancaSettings::class)->impersonation_timeout_minutos)) {
            $this->encerrar->execute();
            session()->flash('warning', 'Personificação expirada — você voltou à sua conta.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar na cadeia**

Em `routes/admin.php:94`, no array de middlewares do grupo autenticado, inserir `EncerrarImpersonationExpirada` logo após `'admin.auth'`:

```php
Route::prefix('admin')->name('admin.')->middleware([App\Http\Middleware\EnsureSystemConfigured::class, 'admin.auth', App\Http\Middleware\EncerrarImpersonationExpirada::class, App\Http\Middleware\CheckInactivity::class, App\Http\Middleware\EnsureTwoFactorEnabled::class, App\Http\Middleware\DefinirContextoTenant::class])->group(function () use ($placeholder): void {
```

- [ ] **Step 5: Run test to verify it passes**

Run: `ddev artisan test --filter=ExpiracaoImpersonationTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/EncerrarImpersonationExpirada.php routes/admin.php tests/Feature/Admin/Impersonation/ExpiracaoImpersonationTest.php
git commit -m "feat(admin): expiração automática da personificação"
```

---

## Task 10: `EnsureTwoFactorEnabled` pula exigência durante a personificação

**Files:**

- Modify: `app/Http/Middleware/EnsureTwoFactorEnabled.php:21-32`
- Test: `tests/Feature/Admin/Impersonation/DoisFatoresDuranteImpersonationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('não força configurar 2FA do alvo enquanto personificando', function (): void {
    $settings = app(SegurancaSettings::class);
    $settings->exigir_2fa_admin = true;
    $settings->save();

    $original = criarAdminUser('original@teste.com'); // sem 2FA
    $alvo = criarAdminUser('alvo@teste.com');         // sem 2FA

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    // Sem o bypass, o middleware forçaria um redirect 302 para a tela de 2FA;
    // 200 prova que a personificação dispensou a exigência.
    $this->get(route('admin.dashboard'))->assertOk();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=DoisFatoresDuranteImpersonationTest`
Expected: FAIL — responde 302 (redirect para `admin.conta.seguranca`), não 200.

- [ ] **Step 3: Ajustar o middleware**

Em `app/Http/Middleware/EnsureTwoFactorEnabled.php`, adicionar o import e a condição de bypass:

```php
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
```

E na cláusula `if`, adicionar `&& ! app(ImpersonationContext::class)->ativo()`:

```php
        if ($usuario instanceof AdminUser
            && ! app(ImpersonationContext::class)->ativo()
            && ! $usuario->hasTwoFactorEnabled()
            && app(SegurancaSettings::class)->exigir_2fa_admin
            && ! $request->routeIs('admin.conta.seguranca')
            && ! $request->routeIs('admin.logout')) {
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=DoisFatoresDuranteImpersonationTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/EnsureTwoFactorEnabled.php tests/Feature/Admin/Impersonation/DoisFatoresDuranteImpersonationTest.php
git commit -m "feat(admin): não exigir 2FA do alvo durante personificação"
```

---

## Task 11: Travas nas ações de segurança da conta

**Files:**

- Modify: `app/Livewire/Admin/Conta/SegurancaConta.php:40-90` (ativar/confirmar/regenerar/desativar)
- Test: `tests/Feature/Admin/Impersonation/TravasSegurancaContaTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Conta\SegurancaConta;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('bloqueia ativar 2FA enquanto personificando', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
        'auth.password_confirmed_at' => time(),
    ])->actingAs($alvo, 'admin');

    Livewire::test(SegurancaConta::class)->call('ativar');
})->throws(AuthorizationException::class);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=TravasSegurancaContaTest`
Expected: FAIL — `ativar` executa normalmente.

- [ ] **Step 3: Adicionar a trava nas ações sensíveis**

Em `app/Livewire/Admin/Conta/SegurancaConta.php`, adicionar o import:

```php
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
```

E como primeira linha de cada método sensível (`ativar`, `confirmar`, `regenerar`, `desativar`):

```php
        app(ImpersonationContext::class)->garantirNaoPersonificando();
```

Exemplo no `ativar()`:

```php
    public function ativar(): void
    {
        app(ImpersonationContext::class)->garantirNaoPersonificando();
        $this->ensurePasswordIsConfirmed();

        $usuario = $this->usuario();
        // ... (resto inalterado)
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=TravasSegurancaContaTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Conta/SegurancaConta.php tests/Feature/Admin/Impersonation/TravasSegurancaContaTest.php
git commit -m "feat(admin): trava 2FA da conta durante personificação"
```

---

## Task 12: Logout audita o encerramento da personificação

**Files:**

- Modify: `app/Http/Controllers/Admin/Auth/LogoutController.php:14-22`
- Test: `tests/Feature/Admin/Impersonation/LogoutDuranteImpersonationTest.php`

> Nota: `session()->invalidate()` já apaga as chaves de impersonation — não há estado órfão. O único acréscimo é registrar o evento de auditoria de encerramento antes de invalidar, para a trilha não "perder" o fim.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logout durante a personificação encerra tudo e audita', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte',
    ])->actingAs($alvo, 'admin');

    $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));

    expect(Auth::guard('admin')->check())->toBeFalse()
        ->and(app(ImpersonationContext::class)->ativo())->toBeFalse()
        ->and(Activity::query()->where('log_name', 'impersonation')->where('event', 'encerrada')->exists())
        ->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=LogoutDuranteImpersonationTest`
Expected: FAIL — sem evento `encerrada`.

- [ ] **Step 3: Auditar no logout**

Substituir o corpo de `app/Http/Controllers/Admin/Auth/LogoutController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    public function __invoke(Request $request, ImpersonationContext $context): RedirectResponse
    {
        if ($context->ativo()) {
            $originalId = $context->originalId();
            $original = $originalId !== null ? AdminUser::find($originalId) : null;
            $context->encerrar();

            activity('impersonation')
                ->causedBy($original)
                ->performedOn(Auth::guard('admin')->user())
                ->event('encerrada')
                ->log('Personificação encerrada (logout)');
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=LogoutDuranteImpersonationTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/Auth/LogoutController.php tests/Feature/Admin/Impersonation/LogoutDuranteImpersonationTest.php
git commit -m "feat(admin): logout audita encerramento da personificação"
```

---

## Task 13: Banner global

**Files:**

- Create: `resources/views/components/admin/impersonation-banner.blade.php`
- Modify: `resources/views/components/admin/layout.blade.php:51` (após `<div class="wrapper">`)
- Test: `tests/Feature/Admin/Impersonation/BannerImpersonationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renderiza o banner com o nome do alvo e a rota de saída quando personificando', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = AdminUser::create([
        'nome' => 'Maria Alvo',
        'email' => 'maria@teste.com',
        'password' => bcrypt('password'),
        'ativo' => true,
    ]);

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte ao cliente',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Maria Alvo')
        ->assertSee(route('admin.impersonation.sair'));
});

it('não renderiza o banner fora de uma personificação', function (): void {
    $user = criarAdminUser('u@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.impersonation.sair'));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=BannerImpersonationTest`
Expected: FAIL — banner não existe.

- [ ] **Step 3: Criar o banner**

`resources/views/components/admin/impersonation-banner.blade.php`:

```blade
@php
    $impersonation = app(\HT2ML\Core\Support\Impersonation\ImpersonationContext::class);
@endphp

@if ($impersonation->ativo())
    @php
        $alvo = auth('admin')->user();
        $timeout = app(\HT2ML\Core\Settings\SegurancaSettings::class)->impersonation_timeout_minutos;
        $expiraEm = ($impersonation->iniciadoEm() ?? time()) + $timeout * 60;
    @endphp
    <div
        class="bg-warning/15 text-warning-700 border-warning/30 flex flex-wrap items-center justify-between gap-3 border-b px-4 py-2 text-sm"
        x-data="{
            expiraEm: {{ $expiraEm }},
            restante: '',
            tick() {
                const s = Math.max(0, this.expiraEm - Math.floor(Date.now() / 1000));
                const m = Math.floor(s / 60).toString().padStart(2, '0');
                const r = (s % 60).toString().padStart(2, '0');
                this.restante = `${m}:${r}`;
            },
        }"
        x-init="tick(); setInterval(() => tick(), 1000)"
    >
        <div class="flex items-center gap-2">
            <span class="iconify tabler--user-shield"></span>
            <span>
                Você está personificando <strong>{{ $alvo?->nome }}</strong>
                @if ($impersonation->motivo())
                    · {{ $impersonation->motivo() }}
                @endif
                · expira em <span x-text="restante" class="font-mono"></span>
            </span>
        </div>

        <form method="POST" action="{{ route('admin.impersonation.sair') }}">
            @csrf
            <button
                type="submit"
                class="btn btn-sm bg-warning/25 hover:bg-warning/35 text-warning-800 inline-flex items-center gap-x-1.5"
            >
                <span class="iconify tabler--logout"></span>
                Sair da personificação
            </button>
        </form>
    </div>
@endif
```

Em `resources/views/components/admin/layout.blade.php`, logo após `<div class="wrapper">` (linha 51) e antes de `<x-admin.topbar />`:

```blade
<x-admin.impersonation-banner />
```

- [ ] **Step 4: Run test to verify it passes**

Run: `ddev artisan test --filter=BannerImpersonationTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/admin/impersonation-banner.blade.php resources/views/components/admin/layout.blade.php tests/Feature/Admin/Impersonation/BannerImpersonationTest.php
git commit -m "feat(admin): banner global de personificação"
```

---

## Task 14: Modal de entrada + ação na tabela de Usuários

**Files:**

- Create: `app/Livewire/Admin/Impersonation/IniciarImpersonation.php`
- Create: `resources/views/livewire/admin/impersonation/iniciar-impersonation.blade.php`
- Modify: `app/Livewire/Admin/Usuarios/UsuariosTable.php:145-171` (botão de ação)
- Modify: `resources/views/livewire/admin/usuarios/index-usuarios.blade.php:12` (montar o modal)
- Test: `tests/Feature/Admin/Impersonation/IniciarImpersonationModalTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Livewire\Admin\Impersonation\IniciarImpersonation;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('inicia a personificação a partir do modal e redireciona ao dashboard', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($ator, 'admin');

    Livewire::test(IniciarImpersonation::class)
        ->call('abrir', $alvo->id)
        ->set('motivo', 'reproduzir problema relatado')
        ->call('confirmarEntrada')
        ->assertRedirect(route('admin.dashboard'));

    expect(app(ImpersonationContext::class)->ativo())->toBeTrue();
});

it('exige motivo com no mínimo 5 caracteres', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($ator, 'admin');

    Livewire::test(IniciarImpersonation::class)
        ->call('abrir', $alvo->id)
        ->set('motivo', 'oi')
        ->call('confirmarEntrada')
        ->assertHasErrors(['motivo']);

    expect(app(ImpersonationContext::class)->ativo())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `ddev artisan test --filter=IniciarImpersonationModalTest`
Expected: FAIL — componente não existe.

- [ ] **Step 3: Criar o componente Livewire**

`app/Livewire/Admin/Impersonation/IniciarImpersonation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Impersonation;

use App\Actions\Admin\Impersonation\IniciarImpersonationAction;
use App\Exceptions\AccessException;
use App\Livewire\Concerns\ConfirmsPassword;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modal de entrada na personificação, acionado pela ação "Entrar como" da tabela
 * de Usuários. Coleta o motivo e reconfirma a senha (ConfirmsPassword) antes de
 * delegar à IniciarImpersonationAction.
 */
class IniciarImpersonation extends Component
{
    use ConfirmsPassword;

    public bool $aberto = false;

    public ?int $alvoId = null;

    public string $alvoNome = '';

    public string $motivo = '';

    #[On('impersonation::abrir')]
    public function abrir(int $id): void
    {
        $alvo = AdminUser::findOrFail($id);
        $this->authorize('impersonate', $alvo);

        $this->alvoId = $alvo->id;
        $this->alvoNome = (string) $alvo->getAttribute('nome');
        $this->motivo = '';
        $this->resetErrorBag();
        $this->aberto = true;
    }

    public function confirmarEntrada(): void
    {
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);
        $this->iniciarConfirmacaoDeSenha('iniciar');
    }

    public function iniciar(): void
    {
        $this->ensurePasswordIsConfirmed();
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);

        $alvo = AdminUser::findOrFail($this->alvoId);
        $this->authorize('impersonate', $alvo);

        try {
            app(IniciarImpersonationAction::class)->execute($this->ator(), $alvo, $this->motivo);
        } catch (AccessException $e) {
            $this->addError('motivo', $e->getMessage());

            return;
        }

        $this->redirect(route('admin.dashboard'));
    }

    public function fechar(): void
    {
        $this->aberto = false;
        $this->reset('alvoId', 'alvoNome', 'motivo');
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.impersonation.iniciar-impersonation');
    }

    private function ator(): AdminUser
    {
        $ator = Auth::guard('admin')->user();

        assert($ator instanceof AdminUser);

        return $ator;
    }
}
```

- [ ] **Step 4: Criar a view do modal**

`resources/views/livewire/admin/impersonation/iniciar-impersonation.blade.php`:

```blade
<div>
    @if ($aberto)
        <div class="fixed inset-0 z-80 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" wire:click="fechar"></div>

            <div class="border-default-300 bg-card relative z-10 w-full max-w-md rounded-xl border p-6 shadow-lg">
                <h3 class="text-body-color text-lg font-semibold">Entrar como {{ $alvoNome }}</h3>
                <p class="text-default-500 mt-1 mb-4 text-sm">Você vai operar o painel como este usuário. Informe o motivo — ele fica registrado na auditoria.</p>

                <form wire:submit="confirmarEntrada">
                    <x-shared.input
                        name="motivo"
                        label="Motivo"
                        wire:model="motivo"
                        placeholder="Ex.: reproduzir problema relatado no chamado #123"
                        autofocus
                    />

                    <div class="mt-5 flex justify-end gap-2">
                        <x-shared.button type="button" variant="light" wire:click="fechar"> Cancelar </x-shared.button>
                        <x-shared.loading-button type="submit" target="confirmarEntrada" icon="tabler--user-shield">
                            Entrar como usuário
                        </x-shared.loading-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include ('admin.partials.confirms-password')
</div>
```

- [ ] **Step 5: Adicionar a ação "Entrar como" na tabela**

Em `app/Livewire/Admin/Usuarios/UsuariosTable.php`, dentro de `actions(AdminUser $row)`, antes de `return $botoes;`:

```php
        if ($ator?->can('impersonate', $row)) {
            $botoes[] = Button::add('impersonate')
                ->slot('Entrar como')
                ->class('btn btn-sm inline-flex items-center gap-x-2 bg-primary/12 text-primary hover:bg-primary/20')
                ->dispatch('impersonation::abrir', ['id' => $row->id]);
        }
```

- [ ] **Step 6: Montar o modal na tela de Usuários**

Em `resources/views/livewire/admin/usuarios/index-usuarios.blade.php`, após `<livewire:admin.usuarios.usuarios-table />`:

```blade
<livewire:admin.impersonation.iniciar-impersonation />
```

- [ ] **Step 7: Run test to verify it passes**

Run: `ddev artisan test --filter=IniciarImpersonationModalTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Admin/Impersonation/IniciarImpersonation.php resources/views/livewire/admin/impersonation/iniciar-impersonation.blade.php app/Livewire/Admin/Usuarios/UsuariosTable.php resources/views/livewire/admin/usuarios/index-usuarios.blade.php tests/Feature/Admin/Impersonation/IniciarImpersonationModalTest.php
git commit -m "feat(admin): modal de entrada e ação 'Entrar como' em Usuários"
```

---

## Task 15: Portão de qualidade + sincronização + documentação

**Files:**

- Modify: `docs/multi-empresa.md` (nota sobre personificação) — opcional, se fizer sentido
- Modify: `CLAUDE.md` — sem alterações esperadas (seção de segurança já genérica)

- [ ] **Step 1: Sincronizar permissões e rodar a suíte completa**

Run:

```bash
ddev artisan access:sync
ddev artisan test --filter=Impersonation
ddev artisan test
```

Expected: toda a suíte verde (inclusive os testes pré-existentes de Usuários/Segurança).

- [ ] **Step 2: Pint + PHPStan**

Run:

```bash
ddev exec ./vendor/bin/pint --dirty
ddev exec ./vendor/bin/phpstan analyse
```

Expected: Pint sem alterações pendentes; PHPStan nível 6 sem erros.

- [ ] **Step 3: Verificação visual (manual, quando o ambiente voltar)**

1. Login como `admin@example.com`; em Usuários, abrir a ação "Entrar como" de um usuário de nível inferior.
2. Informar motivo + reconfirmar senha → cair no dashboard com o banner no topo.
3. Confirmar que 2FA/troca de senha ficam indisponíveis; aguardar/forçar expiração → reverte; botão "Sair" volta ao usuário original.
4. Conferir em `/admin/auditoria` os eventos `iniciada`/`encerrada` (causer = ator real) e uma ação intermediária marcada com `impersonado_por`.

- [ ] **Step 4: Commit final (se houver ajustes de doc/format)**

```bash
git add -A
git commit -m "chore(admin): ajustes finais de qualidade da personificação"
```

---

## Self-review (cobertura do spec)

- Decisão 1 (hierarquia + permissão) → Tasks 4, 5.
- Decisão 2 (act-as com travas) → Tasks 5 (nested), 10 (2FA bypass), 11 (travas de conta).
- Decisão 3 (reconfirmar senha + motivo) → Task 14 (ConfirmsPassword + campo motivo).
- Decisão 4 (teto de tempo + saída manual) → Tasks 2, 9 (expiração), 8 (saída).
- Decisão 5 (auditoria ao alvo, marcando o real) → Tasks 5, 6, 7, 12.
- Banner + entrada/saída UI → Tasks 8, 13, 14.
- Casos de borda (self/inativo/super-admin/nested/tenant/original inválido/logout) → Tasks 5, 6, 12.
