# Endurecimento de segurança — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Endurecer o acesso admin com lockout de conta, rate-limit configurável (cobrindo o reset de senha), alertas de segurança por e-mail aos super-admins e enforcement do status `ativo`.

**Architecture:** Coluna durável `bloqueado_ate` + contagem no `RateLimiter` (serviço `ControleLockout`); helper `LimiteTentativas` lê limites de `SegurancaSettings` e é reusado por Login/2FA/ForgotPassword; alertas via `Notification` em fila (`AlertaSeguranca` + `AlertaSegurancaNotification`); `ativo` aplicado no `Login` + middleware `GarantirContaAtiva`.

**Tech Stack:** Laravel 13, PHP 8.4, Livewire 4, spatie/laravel-permission, spatie/laravel-settings, Notifications (fila `emails`/Horizon), Pest 4, Pint, PHPStan nível 6.

**Spec:** `docs/superpowers/specs/2026-06-05-endurecimento-seguranca-design.md` · **Branch:** `feat/endurecimento`

**Comandos:** testes `ddev artisan test --filter='<nome>'` · Pint `ddev exec ./vendor/bin/pint --dirty` · PHPStan `ddev exec ./vendor/bin/phpstan analyse`

> Convenções (CLAUDE.md): `declare(strict_types=1)`, type hints/return types, serviços API-ready, PT-BR, sem CSS customizado, fila `emails` para mail.
>
> **Lições (aplicar):** (1) `Classe::class` sem `use` vira a string do nome curto → erros enganosos ("ComponentNotFound"/"Target class [Namespace\Errado]"); confira os `use`. (2) O Pint remove import não usado ENTRE duas edições — adicione import + uso na MESMA edição. (3) `Livewire::test()` não vê `$this->withSession()`; use `session([...])`. (4) Alertas: `Notification::fake()` + `assertSentTo`. (5) Helpers de teste: `criarAdminUser($email)`, `criarRoleAdmin($name, $nivel)` em `tests/Pest.php`. (6) Commitar SÓ os arquivos da task (nunca `yarn.lock`/`.workflows/`).

---

## Estrutura de arquivos

**Criar:** `app/Services/Admin/Security/{LimiteTentativas,ControleLockout,AlertaSeguranca}.php` · `app/Enums/TipoAlertaSeguranca.php` · `app/Notifications/AlertaSegurancaNotification.php` · `app/Http/Middleware/GarantirContaAtiva.php` · migrations (`add_bloqueado_ate_to_admin_users`, settings) · testes em `tests/Feature/Admin/Seguranca/`.

**Modificar:** `app/Settings/SegurancaSettings.php` · `app/DTOs/Admin/Settings/SegurancaSettingsDTO.php` · `app/Actions/Admin/Settings/SaveSegurancaSettingsAction.php` · `app/Livewire/Admin/Configuracao/AbaSeguranca.php` (+ view) · `app/Livewire/Admin/Auth/{Login,TwoFactorChallenge,ForgotPassword}.php` · `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php` · `app/Livewire/Admin/Usuarios/UsuariosTable.php` · `routes/admin.php` · `app/Models/AdminUser.php`.

---

## Task 1: Novas propriedades em SegurancaSettings

**Files:** Modify `app/Settings/SegurancaSettings.php` · Create `database/settings/2026_06_05_170000_add_endurecimento_settings.php` · Test `tests/Feature/Admin/Seguranca/EndurecimentoSettingsTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expõe os settings de endurecimento com defaults', function (): void {
    $s = app(SegurancaSettings::class);
    expect($s->login_max_tentativas)->toBe(5)
        ->and($s->login_janela_minutos)->toBe(1)
        ->and($s->lockout_max_falhas)->toBe(10)
        ->and($s->lockout_duracao_minutos)->toBe(15)
        ->and($s->alertas_seguranca_habilitados)->toBeTrue()
        ->and($s->alerta_login_super_admin)->toBeFalse();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=EndurecimentoSettingsTest` → FALHA (MissingSettings).

- [ ] **Step 3: Propriedades** — em `app/Settings/SegurancaSettings.php`, após `public int $impersonation_timeout_minutos;`:

```php
    public int $login_max_tentativas;

    public int $login_janela_minutos;

    public int $lockout_max_falhas;

    public int $lockout_duracao_minutos;

    public bool $alertas_seguranca_habilitados;

    public bool $alerta_login_super_admin;
```

- [ ] **Step 4: Migration de settings** — `database/settings/2026_06_05_170000_add_endurecimento_settings.php`:

```php
<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seguranca.login_max_tentativas', 5);
        $this->migrator->add('seguranca.login_janela_minutos', 1);
        $this->migrator->add('seguranca.lockout_max_falhas', 10);
        $this->migrator->add('seguranca.lockout_duracao_minutos', 15);
        $this->migrator->add('seguranca.alertas_seguranca_habilitados', true);
        $this->migrator->add('seguranca.alerta_login_super_admin', false);
    }
};
```

- [ ] **Step 5: Verde** — `ddev artisan migrate --force && ddev artisan test --filter=EndurecimentoSettingsTest` → PASSA.

- [ ] **Step 6: Commit**

```bash
git add app/Settings/SegurancaSettings.php database/settings/2026_06_05_170000_add_endurecimento_settings.php tests/Feature/Admin/Seguranca/EndurecimentoSettingsTest.php
git commit -m "feat(admin): settings de endurecimento de segurança"
```

---

## Task 2: Coluna `bloqueado_ate` no AdminUser

**Files:** Create `database/migrations/2026_06_05_171000_add_bloqueado_ate_to_admin_users.php` · Modify `app/Models/AdminUser.php` · Test `tests/Feature/Admin/Seguranca/BloqueioContaTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('estaBloqueada reflete bloqueado_ate', function (): void {
    $user = criarAdminUser('u@teste.com');
    expect($user->estaBloqueada())->toBeFalse();

    $user->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();
    expect($user->fresh()->estaBloqueada())->toBeTrue();

    $user->forceFill(['bloqueado_ate' => now()->subMinute()])->save();
    expect($user->fresh()->estaBloqueada())->toBeFalse();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=BloqueioContaTest` → FALHA.

- [ ] **Step 3: Migration** — `database/migrations/2026_06_05_171000_add_bloqueado_ate_to_admin_users.php`:

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
            $table->timestamp('bloqueado_ate')->nullable()->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            $table->dropColumn('bloqueado_ate');
        });
    }
};
```

- [ ] **Step 4: Model** — em `app/Models/AdminUser.php`: adicionar `'bloqueado_ate'` ao `$fillable`; em `casts()` adicionar `'bloqueado_ate' => 'datetime'`; e o método (após `hasTwoFactorEnabled()`):

```php
    /**
     * A conta está temporariamente bloqueada por excesso de falhas de login.
     */
    public function estaBloqueada(): bool
    {
        return $this->bloqueado_ate !== null && $this->bloqueado_ate->isFuture();
    }
```

- [ ] **Step 5: Verde** — `ddev artisan migrate --force && ddev artisan test --filter=BloqueioContaTest` → PASSA.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_06_05_171000_add_bloqueado_ate_to_admin_users.php app/Models/AdminUser.php tests/Feature/Admin/Seguranca/BloqueioContaTest.php
git commit -m "feat(admin): coluna bloqueado_ate e estaBloqueada no AdminUser"
```

---

## Task 3: Enum + Notification de alerta

**Files:** Create `app/Enums/TipoAlertaSeguranca.php`, `app/Notifications/AlertaSegurancaNotification.php` · Test `tests/Feature/Admin/Seguranca/AlertaNotificationTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Enums\TipoAlertaSeguranca;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('monta o e-mail do alerta com assunto e contexto', function (): void {
    $user = criarAdminUser('dest@teste.com');
    $notif = new AlertaSegurancaNotification(TipoAlertaSeguranca::ContaBloqueada, ['usuario' => 'Fulano', 'email' => 'f@x.com']);

    $mail = $notif->toMail($user);

    expect($mail->subject)->toContain('Conta bloqueada')
        ->and(implode(' ', $mail->introLines))->toContain('Fulano');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=AlertaNotificationTest` → FALHA.

- [ ] **Step 3: Enum** — `app/Enums/TipoAlertaSeguranca.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoAlertaSeguranca: string
{
    case ContaBloqueada = 'conta-bloqueada';
    case PersonificacaoIniciada = 'personificacao-iniciada';
    case LoginSuperAdmin = 'login-super-admin';

    public function label(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Conta bloqueada por falhas de login',
            self::PersonificacaoIniciada => 'Personificação iniciada',
            self::LoginSuperAdmin => 'Login de super-administrador',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::ContaBloqueada => 'Uma conta foi bloqueada temporariamente após exceder o limite de tentativas de login.',
            self::PersonificacaoIniciada => 'Um administrador iniciou uma personificação (act-as) de outro usuário.',
            self::LoginSuperAdmin => 'Um super-administrador autenticou no painel.',
        };
    }
}
```

- [ ] **Step 4: Notification** — `app/Notifications/AlertaSegurancaNotification.php`:

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use HT2ML\Core\Enums\TipoAlertaSeguranca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta de atividade suspeita enviado por e-mail aos super-admins. Enfileirado
 * na fila "emails" para não bloquear o fluxo que o disparou.
 */
final class AlertaSegurancaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $contexto
     */
    public function __construct(
        public readonly TipoAlertaSeguranca $tipo,
        public readonly array $contexto,
    ) {
        $this->onQueue('emails');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('[Segurança] ' . $this->tipo->label())
            ->line($this->tipo->descricao());

        foreach ($this->contexto as $rotulo => $valor) {
            $mail->line(ucfirst($rotulo) . ': ' . $valor);
        }

        return $mail->line('Verifique a trilha de auditoria em /admin/auditoria.');
    }
}
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=AlertaNotificationTest` → PASSA.

- [ ] **Step 6: Commit**

```bash
git add app/Enums/TipoAlertaSeguranca.php app/Notifications/AlertaSegurancaNotification.php tests/Feature/Admin/Seguranca/AlertaNotificationTest.php
git commit -m "feat(admin): notification de alerta de segurança"
```

---

## Task 4: Serviço AlertaSeguranca

**Files:** Create `app/Services/Admin/Security/AlertaSeguranca.php` · Test `tests/Feature/Admin/Seguranca/AlertaSegurancaTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
});

it('alerta de conta bloqueada vai aos super-admins ativos', function (): void {
    Notification::fake();
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');

    app(AlertaSeguranca::class)->contaBloqueada($alvo);

    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});

it('respeita o toggle mestre e o de login de super-admin', function (): void {
    Notification::fake();
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    $s = app(SegurancaSettings::class);
    $s->alerta_login_super_admin = false; // default
    $s->save();
    app(AlertaSeguranca::class)->superAdminLogou($super);
    Notification::assertNothingSent();

    $s->alertas_seguranca_habilitados = false;
    $s->save();
    app(AlertaSeguranca::class)->contaBloqueada($super);
    Notification::assertNothingSent();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=AlertaSegurancaTest` → FALHA.

- [ ] **Step 3: Serviço** — `app/Services/Admin/Security/AlertaSeguranca.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use HT2ML\Core\Enums\TipoAlertaSeguranca;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\Notification;

/**
 * Dispara alertas de segurança por e-mail aos super-admins ativos. Respeita os
 * toggles de SegurancaSettings; é no-op quando desabilitado ou sem destinatários.
 */
final class AlertaSeguranca
{
    public function contaBloqueada(AdminUser $usuario): void
    {
        $this->enviar(TipoAlertaSeguranca::ContaBloqueada, [
            'usuario' => (string) $usuario->getAttribute('nome'),
            'email' => (string) $usuario->getAttribute('email'),
        ]);
    }

    public function personificacaoIniciada(AdminUser $ator, AdminUser $alvo): void
    {
        $this->enviar(TipoAlertaSeguranca::PersonificacaoIniciada, [
            'ator' => (string) $ator->getAttribute('nome'),
            'alvo' => (string) $alvo->getAttribute('nome'),
        ]);
    }

    public function superAdminLogou(AdminUser $usuario): void
    {
        if (! app(SegurancaSettings::class)->alerta_login_super_admin) {
            return;
        }

        $this->enviar(TipoAlertaSeguranca::LoginSuperAdmin, [
            'usuario' => (string) $usuario->getAttribute('nome'),
            'email' => (string) $usuario->getAttribute('email'),
        ]);
    }

    /**
     * @param  array<string, string>  $contexto
     */
    private function enviar(TipoAlertaSeguranca $tipo, array $contexto): void
    {
        if (! app(SegurancaSettings::class)->alertas_seguranca_habilitados) {
            return;
        }

        $destinatarios = AdminUser::query()->role('super-admin')->where('ativo', true)->get();

        if ($destinatarios->isEmpty()) {
            return;
        }

        Notification::send($destinatarios, new AlertaSegurancaNotification($tipo, $contexto));
    }
}
```

- [ ] **Step 4: Verde** — `ddev artisan test --filter=AlertaSegurancaTest` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Admin/Security/AlertaSeguranca.php tests/Feature/Admin/Seguranca/AlertaSegurancaTest.php
git commit -m "feat(admin): serviço de alertas de segurança"
```

---

## Task 5: Helper LimiteTentativas

**Files:** Create `app/Services/Admin/Security/LimiteTentativas.php` · Test `tests/Feature/Admin/Seguranca/LimiteTentativasTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('excede após o máximo configurado e limpa', function (): void {
    $s = app(SegurancaSettings::class);
    $s->login_max_tentativas = 2;
    $s->save();

    $lim = app(LimiteTentativas::class);
    $chave = 'teste:x';

    expect($lim->excedido($chave))->toBeFalse();
    $lim->registrar($chave);
    $lim->registrar($chave);
    expect($lim->excedido($chave))->toBeTrue();

    $lim->limpar($chave);
    expect($lim->excedido($chave))->toBeFalse();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=LimiteTentativasTest` → FALHA.

- [ ] **Step 3: Helper** — `app/Services/Admin/Security/LimiteTentativas.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate-limit de ações de autenticação com limites lidos de SegurancaSettings
 * (login_max_tentativas / login_janela_minutos), com piso de 1. Reusado por
 * Login, TwoFactorChallenge e ForgotPassword.
 */
final class LimiteTentativas
{
    public function excedido(string $chave): bool
    {
        return RateLimiter::tooManyAttempts($chave, $this->maximo());
    }

    public function registrar(string $chave): void
    {
        RateLimiter::hit($chave, $this->janelaSegundos());
    }

    public function limpar(string $chave): void
    {
        RateLimiter::clear($chave);
    }

    public function disponivelEm(string $chave): int
    {
        return RateLimiter::availableIn($chave);
    }

    private function maximo(): int
    {
        return max(1, app(SegurancaSettings::class)->login_max_tentativas);
    }

    private function janelaSegundos(): int
    {
        return max(1, app(SegurancaSettings::class)->login_janela_minutos) * 60;
    }
}
```

- [ ] **Step 4: Verde** — `ddev artisan test --filter=LimiteTentativasTest` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Admin/Security/LimiteTentativas.php tests/Feature/Admin/Seguranca/LimiteTentativasTest.php
git commit -m "feat(admin): helper de rate-limit configurável"
```

---

## Task 6: Serviço ControleLockout

**Files:** Create `app/Services/Admin/Security/ControleLockout.php` · Test `tests/Feature/Admin/Seguranca/LockoutContaTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Services\Admin\Security\ControleLockout;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
});

it('bloqueia a conta ao atingir o limite e alerta', function (): void {
    Notification::fake();
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    $s = app(SegurancaSettings::class);
    $s->lockout_max_falhas = 3;
    $s->save();

    $alvo = criarAdminUser('alvo@teste.com');
    $lockout = app(ControleLockout::class);

    $lockout->registrarFalha('alvo@teste.com');
    $lockout->registrarFalha('alvo@teste.com');
    expect($alvo->fresh()->estaBloqueada())->toBeFalse();

    $lockout->registrarFalha('alvo@teste.com');
    expect($alvo->fresh()->estaBloqueada())->toBeTrue();
    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});

it('e-mail inexistente não bloqueia ninguém', function (): void {
    Notification::fake();
    $s = app(SegurancaSettings::class);
    $s->lockout_max_falhas = 1;
    $s->save();

    app(ControleLockout::class)->registrarFalha('naoexiste@teste.com');

    expect(AdminUser::where('email', 'naoexiste@teste.com')->exists())->toBeFalse();
    Notification::assertNothingSent();
});

it('liberar limpa o bloqueio', function (): void {
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();

    app(ControleLockout::class)->liberar($alvo->fresh());

    expect($alvo->fresh()->estaBloqueada())->toBeFalse();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=LockoutContaTest` → FALHA.

- [ ] **Step 3: Serviço** — `app/Services/Admin/Security/ControleLockout.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Admin\Security;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Lockout temporário por conta: conta as falhas de login por e-mail (cache) e, ao
 * exceder o limite, grava bloqueado_ate no usuário (durável) e dispara um alerta.
 * O contador por e-mail é independente do throttle de login (por e-mail+IP).
 */
final class ControleLockout
{
    public function __construct(private readonly AlertaSeguranca $alerta) {}

    public function estaBloqueada(AdminUser $usuario): bool
    {
        return $usuario->estaBloqueada();
    }

    public function registrarFalha(string $email): void
    {
        $chave = $this->chave($email);
        RateLimiter::hit($chave, $this->duracaoMinutos() * 60);

        if (RateLimiter::attempts($chave) < $this->maxFalhas()) {
            return;
        }

        $usuario = AdminUser::where('email', $email)->first();

        if (! $usuario instanceof AdminUser) {
            return;
        }

        $usuario->forceFill(['bloqueado_ate' => now()->addMinutes($this->duracaoMinutos())])->save();
        RateLimiter::clear($chave);
        $this->alerta->contaBloqueada($usuario);
    }

    public function liberar(AdminUser $usuario): void
    {
        if ($usuario->bloqueado_ate !== null) {
            $usuario->forceFill(['bloqueado_ate' => null])->save();
        }

        RateLimiter::clear($this->chave((string) $usuario->getAttribute('email')));
    }

    private function chave(string $email): string
    {
        return 'lockout:' . Str::lower($email);
    }

    private function maxFalhas(): int
    {
        return max(1, app(SegurancaSettings::class)->lockout_max_falhas);
    }

    private function duracaoMinutos(): int
    {
        return max(1, app(SegurancaSettings::class)->lockout_duracao_minutos);
    }
}
```

- [ ] **Step 4: Verde** — `ddev artisan test --filter=LockoutContaTest` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Admin/Security/ControleLockout.php tests/Feature/Admin/Seguranca/LockoutContaTest.php
git commit -m "feat(admin): serviço de lockout de conta"
```

---

## Task 7: Login — throttle configurável + lockout + ativo + alerta

**Files:** Modify `app/Livewire/Admin/Auth/Login.php` · Test `tests/Feature/Admin/Seguranca/LoginEndurecidoTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Auth\Login;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
});

it('recusa login de conta desativada', function (): void {
    $u = criarAdminUser('inativo@teste.com', ativo: false);

    Livewire::test(Login::class)
        ->set('email', 'inativo@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth('admin')->check())->toBeFalse();
});

it('recusa login de conta bloqueada mesmo com senha correta', function (): void {
    $u = criarAdminUser('bloq@teste.com');
    $u->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();

    Livewire::test(Login::class)
        ->set('email', 'bloq@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth('admin')->check())->toBeFalse();
});

it('alerta no login de super-admin quando habilitado', function (): void {
    Notification::fake();
    $s = app(SegurancaSettings::class);
    $s->alerta_login_super_admin = true;
    $s->save();

    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    Livewire::test(Login::class)
        ->set('email', 'super@teste.com')
        ->set('password', 'password')
        ->call('authenticate');

    expect(auth('admin')->id())->toBe($super->id);
    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=LoginEndurecidoTest` → FALHA.

- [ ] **Step 3: Instrumentar o Login** — em `app/Livewire/Admin/Auth/Login.php`:

a) Adicionar imports (junto aos `use`, com seus usos abaixo):

```php
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Services\Admin\Security\ControleLockout;
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
```

b) Substituir o corpo de `authenticate()` por (mantém a lógica de 2FA; troca o RateLimiter inline pelos serviços e adiciona as checagens):

```php
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

        if ($usuario !== null && $usuario->hasTwoFactorEnabled()) {
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
```

- [ ] **Step 4: Verde + regressão** —

```
ddev artisan test --filter=LoginEndurecidoTest
ddev artisan test --filter='EndurecimentoLoginTest|LoginTest|AuditoriaSegurancaTest'
```

Expected: todos PASSAM (os testes de login da Fase A com default 5/min seguem verdes).

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auth/Login.php tests/Feature/Admin/Seguranca/LoginEndurecidoTest.php
git commit -m "feat(admin): login com lockout, status ativo e alerta de super-admin"
```

---

## Task 8: TwoFactorChallenge — throttle configurável + alerta super-admin

**Files:** Modify `app/Livewire/Admin/Auth/TwoFactorChallenge.php` · Test `tests/Feature/Admin/Seguranca/DoisFatoresEndurecidoTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Auth\TwoFactorChallenge;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
});

it('alerta no login com 2FA de super-admin quando habilitado', function (): void {
    Notification::fake();
    $s = app(SegurancaSettings::class);
    $s->alerta_login_super_admin = true;
    $s->save();

    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $secret = app(TwoFactorService::class)->gerarSecret();
    $super->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

    session(['2fa.pending' => ['id' => $super->id, 'remember' => false]]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret))
        ->call('verificar');

    expect(auth('admin')->id())->toBe($super->id);
    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});
```

> Nota: `app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret)` gera o código TOTP válido do momento (aceito por `TwoFactorService::verificarCodigo`, janela ±1). `TwoFactorService::gerarSecret()` é o método correto para o secret.

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=DoisFatoresEndurecidoTest` → FALHA.

- [ ] **Step 3: Instrumentar** — em `app/Livewire/Admin/Auth/TwoFactorChallenge.php`:

a) Adicionar imports:

```php
use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
```

b) No `verificar()`, trocar o bloco de rate-limit por `LimiteTentativas`:

```php
        $limite = app(LimiteTentativas::class);

        if ($limite->excedido($chave)) {
            app(AuditoriaSeguranca::class)->loginBloqueado((string) ($pendente['id'] ?? 'desconhecido'));

            throw ValidationException::withMessages([
                'codigo' => __('auth.throttle', ['seconds' => $limite->disponivelEm($chave)]),
            ]);
        }
```

e a falha de código:

```php
        if (! $this->codigoConfere($service, $usuario)) {
            $limite->registrar($chave);
            app(AuditoriaSeguranca::class)->desafio2faFalhou($usuario);

            return;
        }

        $limite->limpar($chave);
```

(substitui os `RateLimiter::hit/clear`).

c) Após `app(AuditoriaSeguranca::class)->loginBemSucedido($usuario, true);`:

```php
        if ($usuario->hasRole((string) config('access.super_admin_role', 'super-admin'))) {
            app(AlertaSeguranca::class)->superAdminLogou($usuario);
        }
```

(Remover o import `RateLimiter` se ficar sem uso — o Pint cuida; confirme rodando o teste.)

- [ ] **Step 4: Verde + regressão** — `ddev artisan test --filter='DoisFatoresEndurecidoTest|TwoFactorTest|AuditoriaSegurancaTest'` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auth/TwoFactorChallenge.php tests/Feature/Admin/Seguranca/DoisFatoresEndurecidoTest.php
git commit -m "feat(admin): 2FA com throttle configurável e alerta de super-admin"
```

---

## Task 9: ForgotPassword — throttle

**Files:** Modify `app/Livewire/Admin/Auth/ForgotPassword.php` · Test `tests/Feature/Admin/Seguranca/ResetThrottleTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Auth\ForgotPassword;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('throttla solicitações de reset após o limite', function (): void {
    Notification::fake();
    $s = app(SegurancaSettings::class);
    $s->login_max_tentativas = 2;
    $s->save();

    criarAdminUser('u@teste.com');

    foreach (range(1, 2) as $_) {
        Livewire::test(ForgotPassword::class)->set('email', 'u@teste.com')->call('sendLink')->assertHasNoErrors();
    }

    Livewire::test(ForgotPassword::class)
        ->set('email', 'u@teste.com')
        ->call('sendLink')
        ->assertHasErrors('email');
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=ResetThrottleTest` → FALHA.

- [ ] **Step 3: Instrumentar** — em `app/Livewire/Admin/Auth/ForgotPassword.php`, adicionar imports e o throttle no início de `sendLink()`:

```php
use HT2ML\Core\Services\Admin\Security\LimiteTentativas;
use Illuminate\Support\Str;
```

No `sendLink()`, após `$this->validate();`:

```php
        $limite = app(LimiteTentativas::class);
        $chave = 'reset:' . Str::lower($this->email) . '|' . (request()->ip() ?? 'desconhecido');

        if ($limite->excedido($chave)) {
            $this->addError('email', 'Muitas solicitações. Tente novamente mais tarde.');

            return;
        }

        $limite->registrar($chave);
```

(O restante — `Password::broker(...)` e o log de `senhaResetSolicitada` — permanece.)

- [ ] **Step 4: Verde + regressão** — `ddev artisan test --filter='ResetThrottleTest|ForgotPasswordTest'` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Auth/ForgotPassword.php tests/Feature/Admin/Seguranca/ResetThrottleTest.php
git commit -m "feat(admin): throttle no pedido de reset de senha"
```

---

## Task 10: Middleware GarantirContaAtiva

**Files:** Create `app/Http/Middleware/GarantirContaAtiva.php` · Modify `routes/admin.php` · Test `tests/Feature/Admin/Seguranca/ContaAtivaTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('desloga um usuário desativado durante a sessão', function (): void {
    $u = criarAdminUser('u@teste.com');
    $this->actingAs($u, 'admin');
    $u->forceFill(['ativo' => false])->save();

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});

it('mantém um usuário ativo', function (): void {
    $u = criarAdminUser('u@teste.com');
    $this->actingAs($u, 'admin')->get(route('admin.dashboard'))->assertOk();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=ContaAtivaTest` → FALHA (1º caso: sessão segue ativa).

- [ ] **Step 3: Middleware** — `app/Http/Middleware/GarantirContaAtiva.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use HT2ML\Core\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que apenas contas ativas mantêm a sessão admin: um usuário desativado
 * durante a sessão é deslogado na requisição seguinte.
 */
final class GarantirContaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('admin')->user();

        if ($usuario instanceof AdminUser && ! $usuario->ativo) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->with('warning', 'Sua conta foi desativada.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Registrar na cadeia** — em `routes/admin.php`, no array de middlewares do grupo autenticado, inserir `HT2ML\Core\Http\Middleware\GarantirContaAtiva::class` logo APÓS `'admin.auth'` e ANTES de `HT2ML\Core\Http\Middleware\EncerrarImpersonationExpirada::class`. O array fica:

```php
Route::prefix('admin')->name('admin.')->middleware([HT2ML\Core\Http\Middleware\EnsureSystemConfigured::class, 'admin.auth', HT2ML\Core\Http\Middleware\GarantirContaAtiva::class, HT2ML\Core\Http\Middleware\EncerrarImpersonationExpirada::class, HT2ML\Core\Http\Middleware\CheckInactivity::class, HT2ML\Core\Http\Middleware\EnsureTwoFactorEnabled::class, HT2ML\Core\Http\Middleware\DefinirContextoTenant::class])->group(function () use ($placeholder): void {
```

- [ ] **Step 5: Verde** — `ddev artisan test --filter=ContaAtivaTest` → PASSA.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Middleware/GarantirContaAtiva.php routes/admin.php tests/Feature/Admin/Seguranca/ContaAtivaTest.php
git commit -m "feat(admin): middleware derruba sessão de conta desativada"
```

---

## Task 11: Alerta na personificação

**Files:** Modify `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php` · Test `tests/Feature/Admin/Seguranca/AlertaPersonificacaoTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\Impersonation\IniciarImpersonationAction;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('alerta os super-admins ao iniciar uma personificação', function (): void {
    Notification::fake();
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($super);
    app(IniciarImpersonationAction::class)->execute($super, $alvo, 'suporte ao cliente');

    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=AlertaPersonificacaoTest` → FALHA.

- [ ] **Step 3: Instrumentar** — em `app/Actions/Admin/Impersonation/IniciarImpersonationAction.php`:

a) Adicionar `use HT2ML\Core\Services\Admin\Security\AlertaSeguranca;` e injetar no construtor:

```php
    public function __construct(
        private readonly HierarchyResolver $hierarchy,
        private readonly ImpersonationContext $context,
        private readonly AccessResolver $accessResolver,
        private readonly AlertaSeguranca $alerta,
    ) {}
```

b) No fim do `execute()`, após `$this->accessResolver->invalidar($alvo);`:

```php
        $this->alerta->personificacaoIniciada($ator, $alvo);
```

- [ ] **Step 4: Verde + regressão** — `ddev artisan test --filter='AlertaPersonificacaoTest|Impersonation'` → PASSA (os testes de impersonation seguem verdes; com `Notification` real em fila não enviam de fato em teste, mas não quebram — se algum teste de impersonation falhar por isso, adicionar `Notification::fake()` no seu setup).

- [ ] **Step 5: Commit**

```bash
git add app/Actions/Admin/Impersonation/IniciarImpersonationAction.php tests/Feature/Admin/Seguranca/AlertaPersonificacaoTest.php
git commit -m "feat(admin): alerta de segurança ao iniciar personificação"
```

---

## Task 12: Desbloqueio manual na tabela de Usuários

**Files:** Modify `app/Livewire/Admin/Usuarios/UsuariosTable.php` · Test `tests/Feature/Admin/Seguranca/DesbloqueioContaTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Usuarios\UsuariosTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('super-admin desbloqueia uma conta', function (): void {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');
    $alvo->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();

    $this->actingAs($super, 'admin');

    Livewire::test(UsuariosTable::class)->call('desbloquear', $alvo->id);

    expect($alvo->fresh()->estaBloqueada())->toBeFalse();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=DesbloqueioContaTest` → FALHA.

- [ ] **Step 3: Instrumentar** — em `app/Livewire/Admin/Usuarios/UsuariosTable.php`:

a) Adicionar imports:

```php
use HT2ML\Core\Services\Admin\Security\ControleLockout;
use Livewire\Attributes\On;
```

b) No método `actions(AdminUser $row)`, antes de `return $botoes;`, adicionar a ação condicional:

```php
        if ($row->estaBloqueada() && $ator?->can('update', $row)) {
            $botoes[] = Button::add('desbloquear')
                ->slot('Desbloquear')
                ->class('btn btn-sm inline-flex items-center gap-x-2 bg-info/12 text-info hover:bg-info/20')
                ->dispatch('usuarios::desbloquear', ['id' => $row->id]);
        }
```

c) Adicionar o handler:

```php
    #[On('usuarios::desbloquear')]
    public function desbloquear(int $id, ControleLockout $lockout): void
    {
        $usuario = AdminUser::findOrFail($id);
        $this->authorize('update', $usuario);

        $lockout->liberar($usuario);
        session()->flash('toast.success', 'Conta desbloqueada.');
    }
```

> Nota: a Policy `update` já combina `usuarios.editar` + `HierarchyResolver::podeGerir`. O `dispatch` no botão emite o evento que o `#[On]` escuta (mesmo componente PowerGrid).

- [ ] **Step 4: Verde** — `ddev artisan test --filter=DesbloqueioContaTest` → PASSA.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Usuarios/UsuariosTable.php tests/Feature/Admin/Seguranca/DesbloqueioContaTest.php
git commit -m "feat(admin): desbloqueio manual de conta na tabela de usuários"
```

---

## Task 13: Expor os settings na aba de Segurança

**Files:** Modify `app/DTOs/Admin/Settings/SegurancaSettingsDTO.php`, `app/Actions/Admin/Settings/SaveSegurancaSettingsAction.php`, `app/Livewire/Admin/Configuracao/AbaSeguranca.php` (+ view) · Test `tests/Feature/Admin/Seguranca/AbaSegurancaEndurecimentoTest.php`

- [ ] **Step 1: Teste que falha**

```php
<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Configuracao\AbaSeguranca;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('salva os settings de endurecimento pela aba', function (): void {
    Livewire::test(AbaSeguranca::class)
        ->set('lockout_max_falhas', 7)
        ->set('alerta_login_super_admin', true)
        ->call('salvar');

    $s = app(SegurancaSettings::class);
    expect($s->lockout_max_falhas)->toBe(7)
        ->and($s->alerta_login_super_admin)->toBeTrue();
});
```

- [ ] **Step 2: Rodar e ver falhar** — `ddev artisan test --filter=AbaSegurancaEndurecimentoTest` → FALHA.

- [ ] **Step 3: DTO** — em `app/DTOs/Admin/Settings/SegurancaSettingsDTO.php`, adicionar ao construtor (após `dias_retencao_logs`):

```php
        public int $login_max_tentativas,
        public int $login_janela_minutos,
        public int $lockout_max_falhas,
        public int $lockout_duracao_minutos,
        public bool $alertas_seguranca_habilitados,
        public bool $alerta_login_super_admin,
```

- [ ] **Step 4: SaveAction** — em `SaveSegurancaSettingsAction::execute()`, antes do `$settings->save();`:

```php
            $settings->login_max_tentativas = $dto->login_max_tentativas;
            $settings->login_janela_minutos = $dto->login_janela_minutos;
            $settings->lockout_max_falhas = $dto->lockout_max_falhas;
            $settings->lockout_duracao_minutos = $dto->lockout_duracao_minutos;
            $settings->alertas_seguranca_habilitados = $dto->alertas_seguranca_habilitados;
            $settings->alerta_login_super_admin = $dto->alerta_login_super_admin;
```

- [ ] **Step 5: AbaSeguranca** — em `app/Livewire/Admin/Configuracao/AbaSeguranca.php`:

a) Adicionar as propriedades públicas (com defaults):

```php
    public int $login_max_tentativas = 5;
    public int $login_janela_minutos = 1;
    public int $lockout_max_falhas = 10;
    public int $lockout_duracao_minutos = 15;
    public bool $alertas_seguranca_habilitados = true;
    public bool $alerta_login_super_admin = false;
```

b) No `mount()`, hidratar do `$settings` (as 6 novas).
c) No `salvar()`, passar as 6 novas ao `new SegurancaSettingsDTO(...)`.
d) No `rules()`, adicionar: `login_max_tentativas`/`lockout_max_falhas`/`lockout_duracao_minutos` → `['required','integer','min:1','max:100']`; `login_janela_minutos` → `['required','integer','min:1','max:60']`; os dois bool → `['boolean']`.
e) Na view `resources/views/livewire/admin/configuracao/aba-seguranca.blade.php`, adicionar um bloco "Proteção de acesso" com os campos (use `x-shared.input` numéricos e `x-shared.toggle` para os booleans, seguindo o padrão dos campos existentes na view).

- [ ] **Step 6: Verde + regressão** — `ddev artisan test --filter='AbaSegurancaEndurecimentoTest|Configuracao'` → PASSA.

- [ ] **Step 7: Commit**

```bash
git add app/DTOs/Admin/Settings/SegurancaSettingsDTO.php app/Actions/Admin/Settings/SaveSegurancaSettingsAction.php app/Livewire/Admin/Configuracao/AbaSeguranca.php resources/views/livewire/admin/configuracao/aba-seguranca.blade.php tests/Feature/Admin/Seguranca/AbaSegurancaEndurecimentoTest.php
git commit -m "feat(admin): aba de segurança expõe os controles de endurecimento"
```

---

## Task 14: Portão de qualidade

- [ ] **Step 1:** `ddev artisan access:sync && ddev artisan test` → suíte completa verde (incl. Fase A de login, impersonation, auditoria).
- [ ] **Step 2:** `ddev exec ./vendor/bin/pint --dirty` e `ddev exec ./vendor/bin/phpstan analyse` → sem pendências / sem erros.
- [ ] **Step 3 (visual, quando o router voltar):** disparar lockout (errar a senha N vezes) → conta bloqueada + e-mail no Mailpit; desativar um usuário logado → próxima ação desloga; desbloquear pela tabela de Usuários; conferir os campos na aba de Segurança.
- [ ] **Step 4:** commit final se houver ajuste de formatação (`git add -A -- app config tests database resources` — nunca `yarn.lock`/`.workflows/`).

---

## Self-review (cobertura do spec)

- Decisão 1 (alertas e-mail event-driven) → Tasks 3, 4, 7, 8, 11.
- Decisão 2 (lockout por conta) → Tasks 2, 6, 7, 12.
- Decisão 3 (rate-limit configurável + reset) → Tasks 1, 5, 7, 8, 9.
- Decisão 4 (gatilhos: lockout/personificação/super-admin) → Tasks 6, 11, 7/8.
- Decisão 5 (`ativo` no login + middleware) → Tasks 7, 10.
- Config na UI → Task 13.
