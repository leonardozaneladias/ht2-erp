<?php

declare(strict_types=1);

use App\Notifications\AlertaSegurancaNotification;
use App\Services\Admin\Security\ControleLockout;
use HT2ML\Core\Models\AdminUser;
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
