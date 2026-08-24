<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Livewire\Admin\Conta\SegurancaConta;
use App\Livewire\Admin\Usuarios\FormUsuario;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoAlertaSeguranca;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Notifications\CodigoVerificacaoEmailNotification;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    // Ações sensíveis exigem senha e/ou segundo fator confirmados; aqui o foco é
    // o 2FA por e-mail, então marcamos ambos como recém-confirmados.
    session()->put('auth.password_confirmed_at', time());
    session()->put('auth.two_factor_confirmed_at', time());
});

/**
 * Liga (ou desliga) o switch global de 2FA por e-mail.
 */
function permitir2faEmail(bool $permitir = true): void
{
    $settings = app(SegurancaSettings::class);
    $settings->permitir_2fa_email = $permitir;
    $settings->save();
}

/**
 * Recupera o código em claro da última notificação de verificação enviada
 * (capturado via Notification::fake) — o código não é persistido, só o hash.
 */
function codigoEmail2faDe(AdminUser $admin): string
{
    $codigo = '';

    Notification::assertSentTo(
        $admin,
        CodigoVerificacaoEmailNotification::class,
        function (CodigoVerificacaoEmailNotification $n) use (&$codigo): bool {
            $codigo = $n->codigo;

            return true;
        },
    );

    return $codigo;
}

it('exige o segundo fator no login quando só o e-mail está habilitado', function () {
    permitir2faEmail();
    AdminUser::factory()->comEmailDoisFatores()->create(['email' => 'email2fa@teste.com']);

    Livewire::test(Login::class)
        ->set('email', 'email2fa@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.two-factor-challenge'));

    expect(auth('admin')->check())->toBeFalse()
        ->and(session()->has('2fa.pending'))->toBeTrue();
});

it('não exige o segundo fator quando o switch global de e-mail está desligado', function () {
    permitir2faEmail(false);
    AdminUser::factory()->comEmailDoisFatores()->create(['email' => 'email2fa@teste.com']);

    Livewire::test(Login::class)
        ->set('email', 'email2fa@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue();
});

it('envia o código por e-mail ao chegar no desafio (usuário só-e-mail)', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = AdminUser::factory()->comEmailDoisFatores()->create();

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)->assertSet('usarEmail', true);

    Notification::assertSentTo($admin, CodigoVerificacaoEmailNotification::class);
});

it('autentica ao informar um código de e-mail válido', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = AdminUser::factory()->comEmailDoisFatores()->create();

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    $componente = Livewire::test(TwoFactorChallenge::class);
    $codigo = codigoEmail2faDe($admin);

    $componente->set('codigo', $codigo)
        ->call('verificar')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue();
});

it('rejeita um código de e-mail inválido sem autenticar', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = AdminUser::factory()->comEmailDoisFatores()->create();

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', '000000')
        ->call('verificar')
        ->assertHasErrors(['codigo']);

    expect(auth('admin')->check())->toBeFalse();
});

it('oferece o e-mail como método alternativo quando o usuário tem TOTP', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = AdminUser::factory()->comDoisFatores()->comEmailDoisFatores()->create();

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)
        ->assertSet('usarEmail', false)
        ->assertSee('Receber um código por e-mail')
        ->call('usarMetodoEmail')
        ->assertSet('usarEmail', true);

    Notification::assertSentTo($admin, CodigoVerificacaoEmailNotification::class);
});

it('consome o código de e-mail após o uso (single-use)', function () {
    Notification::fake();
    $admin = criarAdminUser();
    $service = app(TwoFactorService::class);

    $service->dispararCodigoEmail($admin);
    $codigo = codigoEmail2faDe($admin);

    expect($service->verificarCodigoEmail($admin, $codigo))->toBeTrue()
        ->and($service->verificarCodigoEmail($admin, $codigo))->toBeFalse();
});

it('respeita o cooldown entre envios do código por e-mail', function () {
    Notification::fake();
    $admin = criarAdminUser();
    $service = app(TwoFactorService::class);

    expect($service->dispararCodigoEmail($admin))->toBeTrue()
        ->and($service->dispararCodigoEmail($admin))->toBeFalse();
});

it('ativa o 2FA por e-mail na conta após confirmar o código', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = criarAdminUser();

    $componente = Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('ativarEmailDoisFatores')
        ->assertSet('configurandoEmail', true);

    $codigo = codigoEmail2faDe($admin);

    $componente->set('codigoEmailConfirmacao', $codigo)
        ->call('confirmarEmailDoisFatores')
        ->assertHasNoErrors();

    expect($admin->fresh()->two_factor_email_enabled)->toBeTrue();

    Notification::assertSentTo(
        $admin,
        AlertaSegurancaNotification::class,
        fn (AlertaSegurancaNotification $n): bool => $n->tipo === TipoAlertaSeguranca::DoisFatoresEmailAtivado,
    );
});

it('rejeita a ativação por e-mail na conta com código inválido', function () {
    Notification::fake();
    permitir2faEmail();
    $admin = criarAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('ativarEmailDoisFatores')
        ->set('codigoEmailConfirmacao', '000000')
        ->call('confirmarEmailDoisFatores')
        ->assertHasErrors(['codigoEmailConfirmacao']);

    expect($admin->fresh()->two_factor_email_enabled)->toBeFalse();
});

it('desativa o 2FA por e-mail na conta', function () {
    permitir2faEmail();
    $admin = AdminUser::factory()->comEmailDoisFatores()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('desativarEmailDoisFatores');

    expect($admin->fresh()->two_factor_email_enabled)->toBeFalse();
});

it('não mostra o card de e-mail quando o switch global está desligado', function () {
    permitir2faEmail(false);
    $admin = criarAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->assertDontSee('Código por e-mail');
});

it('permite a um admin habilitar o 2FA por e-mail de outro usuário', function () {
    permitir2faEmail();
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($ator, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('emailDoisFatoresAlvo', true)
        ->call('salvarDoisFatoresEmail')
        ->assertHasNoErrors();

    expect($alvo->fresh()->two_factor_email_enabled)->toBeTrue();
});

it('bloqueia quem não tem a permissão de gerenciar 2FA', function () {
    permitir2faEmail();
    $role = criarRoleAdmin('editor-teste', 50);
    $role->givePermissionTo('usuarios.editar');
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('editor-teste');
    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($ator, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('emailDoisFatoresAlvo', true)
        ->call('salvarDoisFatoresEmail')
        ->assertForbidden();

    expect($alvo->fresh()->two_factor_email_enabled)->toBeFalse();
});

it('considera o 2FA por e-mail suficiente para a política exigir_2fa_admin', function () {
    $seg = app(SegurancaSettings::class);
    $seg->permitir_2fa_email = true;
    $seg->exigir_2fa_admin = true;
    $seg->save();

    $admin = AdminUser::factory()->comEmailDoisFatores()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk();
});
