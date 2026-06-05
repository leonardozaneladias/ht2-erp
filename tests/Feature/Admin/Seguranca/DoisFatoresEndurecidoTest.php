<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Notifications\AlertaSegurancaNotification;
use App\Services\Admin\Security\TwoFactorService;
use App\Settings\SegurancaSettings;
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
        ->set('codigo', app(PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret))
        ->call('verificar');

    expect(auth('admin')->id())->toBe($super->id);
    Notification::assertSentTo($super, AlertaSegurancaNotification::class);
});
