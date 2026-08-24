<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('edita nome e perfis mantendo a senha quando vazia', function () {
    $alvo = AdminUser::create([
        'nome' => 'Antigo',
        'email' => 'alvo@teste.com',
        'password' => Hash::make('originalpass'),
        'ativo' => true,
    ]);
    $alvo->assignRole('gestor');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('nome', 'Novo Nome')
        ->set('password', '')
        ->set('roles', ['super-admin'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.usuarios.index'));

    $alvo->refresh();
    expect($alvo->nome)->toBe('Novo Nome')
        ->and(Hash::check('originalpass', $alvo->password))->toBeTrue()
        ->and($alvo->hasRole('super-admin'))->toBeTrue()
        ->and($alvo->hasRole('gestor'))->toBeFalse();
});

it('permite usar o mesmo e-mail ao editar o próprio usuário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $this->admin->id])
        ->set('email', $this->admin->email)
        ->call('salvar')
        ->assertHasNoErrors();
});
