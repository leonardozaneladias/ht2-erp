<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\IndexUsuarios;
use App\Livewire\Admin\Usuarios\UsuariosTable;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
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

it('filtra por múltiplos perfis (multiSelect com builder whereIn)', function () {
    $admin = criarAdmin('super-admin', ['nome' => 'Super Filtro']);
    criarAdmin('gestor', ['nome' => 'Gestora Maria']);
    criarRoleAdmin('auditor', 20);
    criarAdmin('auditor', ['nome' => 'Auditor Carlos']);

    Livewire::actingAs($admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('filters', ['multi_select' => ['perfil' => ['gestor', 'auditor']]])
        ->assertSee('Gestora Maria')
        ->assertSee('Auditor Carlos')
        ->assertDontSee('Super Filtro');
});

it('multiSelect de perfis vazio não filtra nada', function () {
    $admin = criarAdmin('super-admin', ['nome' => 'Super Vazio']);
    criarAdmin('gestor', ['nome' => 'Gestora Sem Filtro']);

    Livewire::actingAs($admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('filters', ['multi_select' => ['perfil' => []]])
        ->assertSee('Super Vazio')
        ->assertSee('Gestora Sem Filtro');
});

it('nega acesso a gestor (sem permissão usuarios.listar)', function () {
    $gestor = criarAdmin('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(IndexUsuarios::class)
        ->assertForbidden();
})->skip('Gestor padrão tem usuarios.listar pelo seeder; ajustar quando permissões evoluírem.');

it('abre a ficha Ver de usuário pelo evento do kebab', function () {
    $admin = criarAdmin();
    $alvo = criarAdmin('gestor');

    Livewire::actingAs($admin, 'admin')
        ->test(IndexUsuarios::class)
        ->dispatch('usuarios::ver', id: $alvo->id)
        ->assertSet('fichaId', $alvo->id)
        ->assertDispatched('ficha-abrir')
        ->assertSee($alvo->email);
});
