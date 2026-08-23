<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Notifications\AlertaSegurancaNotification;
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

it('recusa login de conta desativada', function (): void {
    criarAdminUser('inativo@teste.com', ativo: false);

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
