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
