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
