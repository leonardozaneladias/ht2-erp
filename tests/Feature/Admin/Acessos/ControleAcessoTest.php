<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Acesso\ControleAcesso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

it('renderiza o hub de controle de acesso', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->assertOk()
        ->assertSee('Controle de acesso');
});

it('abre na lente Perfis e lista os perfis existentes', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->assertSet('lente', 'perfis')
        ->assertSee('super-admin')
        ->assertSee('gestor');
});

it('troca para a lente Pessoas e lista os usuários', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('trocarLente', 'pessoas')
        ->assertSet('lente', 'pessoas')
        ->assertSet('selecionadoId', null)
        ->assertSee('admin@teste.com');
});

it('seleciona um item da lista', function () {
    $perfilId = Spatie\Permission\Models\Role::where('name', 'gestor')->where('guard_name', 'admin')->value('id');

    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('selecionar', $perfilId)
        ->assertSet('selecionadoId', $perfilId);
});

it('ignora lente inválida e mantém Perfis', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('trocarLente', 'inexistente')
        ->assertSet('lente', 'perfis');
});

it('nega acesso a quem não tem permissão de perfis', function () {
    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(ControleAcesso::class)
        ->assertForbidden();
});
