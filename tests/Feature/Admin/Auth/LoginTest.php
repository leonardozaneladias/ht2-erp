<?php

declare(strict_types=1);

use App\Livewire\Admin\Auth\Login;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renderiza o formulário de login', function () {
    Livewire::test(Login::class)
        ->assertSee('Bem-vindo de volta!')
        ->assertSee('E-mail')
        ->assertSee('Senha');
});

it('exibe erro com credenciais inválidas', function () {
    AdminUser::create([
        'nome' => 'Admin',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@teste.com')
        ->set('password', 'senha-errada')
        ->call('authenticate')
        ->assertHasErrors(['email']);
});

it('faz login com credenciais válidas', function () {
    AdminUser::create([
        'nome' => 'Admin',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@teste.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect(route('admin.dashboard'));

    expect(auth('admin')->check())->toBeTrue();
});

it('exibe erro de validação sem e-mail', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['email' => 'required']);
});

it('redireciona para dashboard se já autenticado', function () {
    $admin = AdminUser::create([
        'nome' => 'Admin',
        'email' => 'admin@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(Login::class)
        ->assertRedirect(route('admin.dashboard'));
});
