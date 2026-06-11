<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\FormUsuario;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function criarSuperAdmin(): AdminUser
{
    $admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');

    return $admin;
}

it('cria usuário com role e grava activity log', function () {
    $admin = criarSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Novo Usuário')
        ->set('email', 'novo@teste.com')
        ->set('modoAcesso', 'manual')
        ->set('password', 'SenhaForte1')
        ->set('ativo', true)
        ->set('roles', ['gestor'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.usuarios.index'));

    $novo = AdminUser::where('email', 'novo@teste.com')->firstOrFail();
    expect($novo->nome)->toBe('Novo Usuário')
        ->and($novo->hasRole('gestor'))->toBeTrue()
        ->and(Hash::check('SenhaForte1', $novo->password))->toBeTrue();

    expect(Activity::where('log_name', 'admin_users')
        ->where('event', 'created')
        ->where('subject_id', $novo->id)
        ->exists())->toBeTrue();
});

it('valida e-mail único', function () {
    $admin = criarSuperAdmin();
    AdminUser::create([
        'nome' => 'Existente',
        'email' => 'existe@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Conflito')
        ->set('email', 'existe@teste.com')
        ->set('modoAcesso', 'manual')
        ->set('password', 'SenhaForte1')
        ->call('salvar')
        ->assertHasErrors(['email']);
});

it('valida senha mínima de 8 caracteres', function () {
    $admin = criarSuperAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Teste')
        ->set('email', 'teste@teste.com')
        ->set('modoAcesso', 'manual')
        ->set('password', '123')
        ->call('salvar')
        ->assertHasErrors(['password']);
});
