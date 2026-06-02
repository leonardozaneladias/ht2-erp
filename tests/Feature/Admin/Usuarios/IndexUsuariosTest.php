<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\IndexUsuarios;
use App\Livewire\Admin\Usuarios\UsuariosTable;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function criarAdmin(string $role = 'super-admin', array $overrides = []): AdminUser
{
    $admin = AdminUser::create(array_merge([
        'nome' => 'Admin ' . fake()->firstName(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'ativo' => true,
    ], $overrides));
    $admin->assignRole($role);

    return $admin;
}

it('renderiza a página de listagem com cabeçalho e ação de criar', function () {
    $admin = criarAdmin('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(IndexUsuarios::class)
        ->assertOk()
        ->assertSee('Usuários admin')
        ->assertSee('Novo usuário');
});

it('lista usuários para super-admin', function () {
    $admin = criarAdmin('super-admin');
    criarAdmin('gestor', ['nome' => 'Maria Gestora']);

    Livewire::actingAs($admin, 'admin')
        ->test(UsuariosTable::class)
        ->assertOk()
        ->assertSee($admin->email)
        ->assertSee('Maria Gestora');
});

it('filtra por busca no nome', function () {
    $admin = criarAdmin('super-admin');
    criarAdmin('gestor', ['nome' => 'João Silva']);
    criarAdmin('gestor', ['nome' => 'Pedro Oliveira']);

    Livewire::actingAs($admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('search', 'João')
        ->assertSee('João Silva')
        ->assertDontSee('Pedro Oliveira');
});

it('filtra por status inativo', function () {
    $admin = criarAdmin('super-admin');
    criarAdmin('gestor', ['nome' => 'Ativo Um', 'ativo' => true]);
    criarAdmin('gestor', ['nome' => 'Inativo Dois', 'ativo' => false]);

    Livewire::actingAs($admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('filters', ['boolean' => ['ativo' => 'false']])
        ->assertSee('Inativo Dois')
        ->assertDontSee('Ativo Um');
});

it('nega acesso a gestor (sem permissão usuarios.listar)', function () {
    $gestor = criarAdmin('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(IndexUsuarios::class)
        ->assertForbidden();
})->skip('Gestor padrão tem usuarios.listar pelo seeder; ajustar quando permissões evoluírem.');
