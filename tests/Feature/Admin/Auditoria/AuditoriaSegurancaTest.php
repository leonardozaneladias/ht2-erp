<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\TwoFactorChallenge;
use App\Services\Admin\AuditoriaSeguranca;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
