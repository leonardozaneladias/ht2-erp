<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Livewire\Admin\Configuracao\AbaSeguranca;
use App\Livewire\Admin\Conta\SegurancaConta;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoAlertaSeguranca;
use HT2ML\Core\Notifications\AlertaSegurancaNotification;
use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->google2fa = app(Google2FA::class);
    // Ações sensíveis de 2FA exigem senha e/ou segundo fator confirmados; aqui o
    // foco é o 2FA em si, então marcamos ambos como recém-confirmados.
    session()->put('auth.password_confirmed_at', time());
    session()->put('auth.two_factor_confirmed_at', time());
});

/**
 * Ativa o 2FA diretamente no model (atalho para os testes de login/desafio).
 */
function ativar2fa(HT2ML\Core\Models\AdminUser $admin, string $secret, array $recoveryHashes = []): void
{
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $recoveryHashes,
    ])->save();
}

it('ativa e confirma o 2FA gerando códigos de recuperação', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');

    $componente = Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('ativar')
        ->assertSet('configurando', true);

    $secret = $admin->fresh()->two_factor_secret;
    $otp = $this->google2fa->getCurrentOtp($secret);

    $componente->set('codigoConfirmacao', $otp)
        ->call('confirmar')
        ->assertHasNoErrors();

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeTrue()
        ->and($componente->get('recoveryCodes'))->toHaveCount(8);
});

it('rejeita confirmação com código inválido', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('ativar')
        ->set('codigoConfirmacao', '000000')
        ->call('confirmar')
        ->assertHasErrors(['codigoConfirmacao']);

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('exige o segundo fator no login quando o 2FA está ativo', function () {
    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey());

    Livewire::test(Login::class)
        ->set('email', '2fa@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.two-factor-challenge'));

    expect(auth('admin')->check())->toBeFalse()
        ->and(session()->has('2fa.pending'))->toBeTrue();
});

it('autentica ao informar um código TOTP válido no desafio', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $secret = $this->google2fa->generateSecretKey();
    ativar2fa($admin, $secret);

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', $this->google2fa->getCurrentOtp($secret))
        ->call('verificar')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue();
});

it('autentica com código de recuperação e o consome (single-use)', function () {
    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey(), [
        Hash::make('AAAAA-BBBBB'),
        Hash::make('CCCCC-DDDDD'),
    ]);

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('usarRecovery', true)
        ->set('recoveryCode', 'AAAAA-BBBBB')
        ->call('verificar')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue()
        ->and($admin->fresh()->two_factor_recovery_codes)->toHaveCount(1);
});

it('rejeita código inválido no desafio sem autenticar', function () {
    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey());

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);

    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', '000000')
        ->call('verificar')
        ->assertHasErrors(['codigo']);

    expect(auth('admin')->check())->toBeFalse();
});

it('força a configuração de 2FA quando a política exige', function () {
    $seg = app(SegurancaSettings::class);
    $seg->exigir_2fa_admin = true;
    $seg->save();

    $admin = criarAdminUser('semtfa@teste.com');
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.conta', ['aba' => 'seguranca']));
});

it('desativa o 2FA', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');
    ativar2fa($admin, $this->google2fa->generateSecretKey());

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('desativar');

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('rejeita o reuso do mesmo código TOTP no desafio (anti-replay)', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $secret = $this->google2fa->generateSecretKey();
    ativar2fa($admin, $secret);
    $otp = $this->google2fa->getCurrentOtp($secret);

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);
    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', $otp)
        ->call('verificar')
        ->assertRedirect(route('admin.dashboard'));

    auth('admin')->logout();

    // O mesmo código não vale uma segunda vez.
    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);
    Livewire::test(TwoFactorChallenge::class)
        ->set('codigo', $otp)
        ->call('verificar')
        ->assertHasErrors(['codigo']);

    expect(auth('admin')->check())->toBeFalse();
});

it('exige confirmação de 2FA para desativar (abre o step-up)', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');
    ativar2fa($admin, $this->google2fa->generateSecretKey());

    session()->forget('auth.two_factor_confirmed_at');

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacao2fa', 'desativar')
        ->assertSet('confirmando2fa', true);

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('desativa o 2FA após confirmar o segundo fator (step-up)', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');
    $secret = $this->google2fa->generateSecretKey();
    ativar2fa($admin, $secret);

    session()->forget('auth.two_factor_confirmed_at');

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacao2fa', 'desativar')
        ->set('codigo2fa', $this->google2fa->getCurrentOtp($secret))
        ->call('confirmar2fa')
        ->assertHasNoErrors();

    expect($admin->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('exige step-up de 2FA para salvar a aba Segurança', function () {
    $admin = criarAdminUser('2fa@teste.com');
    $admin->assignRole('super-admin');
    ativar2fa($admin, $this->google2fa->generateSecretKey());

    session()->forget('auth.two_factor_confirmed_at');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaSeguranca::class)
        ->set('lockout_max_falhas', 9)
        ->call('solicitarSalvar')
        ->assertSet('confirmando2fa', true);

    expect(app(SegurancaSettings::class)->lockout_max_falhas)->not->toBe(9);
});

it('cai no fallback de senha ao salvar a aba Segurança sem 2FA', function () {
    $admin = criarAdminUser('semtfa@config.test');
    $admin->assignRole('super-admin');

    session()->forget('auth.two_factor_confirmed_at');
    session()->put('auth.password_confirmed_at', time());

    Livewire::actingAs($admin, 'admin')
        ->test(AbaSeguranca::class)
        ->set('lockout_max_falhas', 8)
        ->call('solicitarSalvar')
        ->assertHasNoErrors();

    expect(app(SegurancaSettings::class)->lockout_max_falhas)->toBe(8);
});

it('notifica o próprio usuário ao ativar o 2FA', function () {
    Notification::fake();

    $admin = criarAdminUser('2fa@teste.com');

    $componente = Livewire::actingAs($admin, 'admin')->test(SegurancaConta::class)->call('ativar');
    $secret = $admin->fresh()->two_factor_secret;

    $componente->set('codigoConfirmacao', $this->google2fa->getCurrentOtp($secret))
        ->call('confirmar')
        ->assertHasNoErrors();

    Notification::assertSentTo(
        $admin,
        AlertaSegurancaNotification::class,
        fn (AlertaSegurancaNotification $n): bool => $n->tipo === TipoAlertaSeguranca::DoisFatoresAtivado,
    );
});

it('alerta o usuário quando um código de recuperação é usado', function () {
    Notification::fake();

    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey(), [Hash::make('AAAAA-BBBBB')]);

    session()->put('2fa.pending', ['id' => $admin->id, 'remember' => false]);
    Livewire::test(TwoFactorChallenge::class)
        ->set('usarRecovery', true)
        ->set('recoveryCode', 'AAAAA-BBBBB')
        ->call('verificar')
        ->assertRedirect(route('admin.dashboard'));

    Notification::assertSentTo(
        $admin,
        AlertaSegurancaNotification::class,
        fn (AlertaSegurancaNotification $n): bool => $n->tipo === TipoAlertaSeguranca::CodigoRecuperacaoUtilizado,
    );
});

it('mostra quantos códigos de recuperação restam', function () {
    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey(), [
        Hash::make('AAAAA-BBBBB'),
        Hash::make('CCCCC-DDDDD'),
        Hash::make('EEEEE-FFFFF'),
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->assertSee('3 códigos de recuperação restantes');
});

it('avisa quando os códigos de recuperação estão acabando', function () {
    $admin = criarAdminUser('2fa@teste.com');
    ativar2fa($admin, $this->google2fa->generateSecretKey(), [Hash::make('AAAAA-BBBBB')]);

    Livewire::actingAs($admin, 'admin')
        ->test(SegurancaConta::class)
        ->assertSee('estão acabando');
});
