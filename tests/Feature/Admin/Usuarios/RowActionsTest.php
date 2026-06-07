<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\UsuariosTable;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->superAdmin = AdminUser::create([
        'nome' => 'Super Admin',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->superAdmin->assignRole('super-admin');
});

it('renderiza as ações da linha agrupadas em um único menu kebab', function () {
    $alvo = AdminUser::create([
        'nome' => 'Maria Alvo',
        'email' => 'maria@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $alvo->assignRole('gestor');

    Livewire::actingAs($this->superAdmin, 'admin')
        ->test(UsuariosTable::class)
        ->assertOk()
        ->assertSeeHtml('tabler--dots-vertical') // gatilho do menu (kebab)
        ->assertSeeHtml('x-data="afRowActions"') // menu Alpine resiliente a morph
        ->assertSee('Editar')
        ->assertSee('Entrar como');
});

it('actionsFromView omite as ações de usuários anonimizados', function () {
    $anonimizado = AdminUser::create([
        'nome' => 'Anônimo',
        'email' => 'anon@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
        'anonimizado_em' => now(),
    ]);
    $anonimizado->assignRole('gestor');

    $tabela = Livewire::actingAs($this->superAdmin, 'admin')
        ->test(UsuariosTable::class)
        ->instance();

    expect($tabela->actionsFromView($anonimizado))->toBeNull();
    expect($tabela->actionsFromView($this->superAdmin))->not->toBeNull();
});

it('solicitarToggleStatus dispara a confirmação temática (sem alterar status ainda)', function () {
    $alvo = AdminUser::create([
        'nome' => 'Alvo Toggle',
        'email' => 'alvotoggle@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $alvo->assignRole('gestor');

    Livewire::actingAs($this->superAdmin, 'admin')
        ->test(UsuariosTable::class)
        ->call('solicitarToggleStatus', $alvo->id)
        ->assertDispatched('confirm')
        ->assertHasNoErrors();

    // Apenas pediu confirmação — o status só muda no onConfirm (usuarios::toggle-status).
    expect($alvo->fresh()->ativo)->toBeTrue();
});
