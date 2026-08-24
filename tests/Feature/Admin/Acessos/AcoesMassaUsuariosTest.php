<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Usuarios\UsuariosTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

it('atribui perfil em massa aos usuários selecionados', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    $a = criarAdminUser('a@teste.com');
    $b = criarAdminUser('b@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('checkboxValues', [$a->id, $b->id])
        ->set('perfilEmMassa', 'gestor')
        ->call('atribuirPerfilEmMassa')
        ->assertHasNoErrors();

    expect($a->fresh()->hasRole('gestor'))->toBeTrue();
    expect($b->fresh()->hasRole('gestor'))->toBeTrue();
});

it('desativa usuários em massa', function () {
    $a = criarAdminUser('a@teste.com');
    $b = criarAdminUser('b@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('checkboxValues', [$a->id, $b->id])
        ->call('alternarStatusEmMassa', false)
        ->assertHasNoErrors();

    expect($a->fresh()->ativo)->toBeFalse();
    expect($b->fresh()->ativo)->toBeFalse();
});

it('limpa a seleção ao concluir a ação', function () {
    $a = criarAdminUser('a@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(UsuariosTable::class)
        ->set('checkboxValues', [$a->id])
        ->call('alternarStatusEmMassa', false)
        ->assertSet('checkboxValues', []);
});

it('perfisAtribuiveis exclui a role super-admin para um gestor', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    Role::findOrCreate('analista', 'admin');
    DB::table('roles')->where('name', 'analista')->update(['nivel' => 10]);

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    $component = Livewire::actingAs($gestor, 'admin')->test(UsuariosTable::class);
    $perfis = $component->get('perfisAtribuiveis');
    $valores = array_column(is_array($perfis) ? $perfis : [], 'value');

    expect($valores)->toContain('analista');
    expect($valores)->not->toContain('super-admin');
    expect($valores)->not->toContain('gestor');
});
