<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Livewire\Admin\Auth\ResetPassword;
use App\Models\AdminUser;
use App\Settings\SegurancaSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('bloqueia novas tentativas de login após exceder o limite', function () {
    AdminUser::create([
        'nome' => 'Alvo',
        'email' => 'rl@teste.com',
        'password' => Hash::make('SenhaForte1'),
        'ativo' => true,
    ]);

    $componente = Livewire::test(Login::class)->set('email', 'rl@teste.com');

    for ($i = 0; $i < 5; $i++) {
        $componente->set('password', "errada{$i}")->call('authenticate');
    }

    // 6ª tentativa, mesmo com a senha correta, deve ser barrada pelo throttle.
    $componente->set('password', 'SenhaForte1')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth('admin')->check())->toBeFalse();
});

it('aplica a política de senha ao redefinir', function () {
    $admin = AdminUser::create([
        'nome' => 'Reset',
        'email' => 'reset@teste.com',
        'password' => Hash::make('SenhaForte1'),
        'ativo' => true,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'reset@teste.com')
        ->set('password', 'fraca')
        ->set('password_confirmation', 'fraca')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

it('redefine a senha quando ela cumpre a política', function () {
    $admin = AdminUser::create([
        'nome' => 'Reset',
        'email' => 'reset@teste.com',
        'password' => Hash::make('SenhaForte1'),
        'ativo' => true,
    ]);

    $token = Password::broker('admins')->createToken($admin);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'reset@teste.com')
        ->set('password', 'NovaSenha2')
        ->set('password_confirmation', 'NovaSenha2')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.login'));

    expect(Hash::check('NovaSenha2', $admin->fresh()->password))->toBeTrue();
});

it('expira a sessão após o tempo de inatividade', function () {
    $seg = app(SegurancaSettings::class);
    $seg->sessao_timeout_minutos = 30;
    $seg->save();

    $admin = criarAdminUser('inativo@teste.com');
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->withSession(['last_activity_at' => time() - (31 * 60)])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));

    expect(auth('admin')->check())->toBeFalse();
});

it('mantém a sessão ativa dentro do tempo de inatividade', function () {
    $seg = app(SegurancaSettings::class);
    $seg->sessao_timeout_minutos = 30;
    $seg->save();

    $admin = criarAdminUser('ativo@teste.com');
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->withSession(['last_activity_at' => time() - (5 * 60)])
        ->get(route('admin.dashboard'))
        ->assertOk();
});
