<?php

declare(strict_types=1);

use App\Services\Admin\AuditoriaSeguranca;
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
