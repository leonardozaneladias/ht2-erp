<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');
});

it('cria usuário com telefone e cargo', function () {
    Notification::fake();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class)
        ->set('nome', 'Com Perfil')
        ->set('email', 'perfil@teste.com')
        ->set('telefone', '(11) 97777-6666')
        ->set('cargo', 'Vendedor')
        ->call('salvar')
        ->assertHasNoErrors();

    $novo = AdminUser::where('email', 'perfil@teste.com')->firstOrFail();

    expect($novo->telefone)->toBe('(11) 97777-6666')
        ->and($novo->cargo)->toBe('Vendedor');
});

it('edita telefone e cargo de um usuário', function () {
    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('telefone', '(21) 96666-5555')
        ->set('cargo', 'Supervisor')
        ->call('salvar')
        ->assertHasNoErrors();

    $alvo->refresh();

    expect($alvo->telefone)->toBe('(21) 96666-5555')
        ->and($alvo->cargo)->toBe('Supervisor');
});
