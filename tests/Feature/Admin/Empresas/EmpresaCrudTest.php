<?php

declare(strict_types=1);

use App\Livewire\Admin\Empresas\EmpresasTable;
use App\Livewire\Admin\Empresas\FormEmpresa;
use App\Livewire\Admin\Empresas\IndexEmpresas;
use App\Models\Empresa;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

it('cria uma empresa com filial matriz', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class)
        ->set('nome', 'Acme')
        ->set('cor_primaria', '#123456')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.empresas.index'));

    $empresa = Empresa::query()->where('nome', 'Acme')->first();

    expect($empresa)->not->toBeNull()
        ->and($empresa->cor_primaria)->toBe('#123456')
        ->and($empresa->filiais()->where('e_matriz', true)->where('nome', 'Matriz')->exists())->toBeTrue();
});

it('valida cor em formato hexadecimal', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class)
        ->set('nome', 'Acme')
        ->set('cor_primaria', 'azul')
        ->call('salvar')
        ->assertHasErrors('cor_primaria');
});

it('edita uma empresa existente', function () {
    $empresa = Empresa::create(['nome' => 'Antiga', 'ativo' => true]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $empresa->id])
        ->assertSet('nome', 'Antiga')
        ->set('nome', 'Nova')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($empresa->fresh()->nome)->toBe('Nova');
});

it('lista empresas no grid', function () {
    Empresa::create(['nome' => 'Visível Ltda', 'ativo' => true]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(EmpresasTable::class)
        ->assertSee('Visível Ltda');
});

it('bloqueia quem não pode listar empresas', function () {
    $semAcesso = criarAdminUser('sem@teste.com');

    Livewire::actingAs($semAcesso, 'admin')
        ->test(IndexEmpresas::class)
        ->assertForbidden();
});
