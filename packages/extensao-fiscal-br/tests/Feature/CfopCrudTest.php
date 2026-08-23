<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\FiscalBr\Livewire\CfopTable;
use HT2ML\FiscalBr\Livewire\FormCfop;
use HT2ML\FiscalBr\Livewire\IndexCfop;
use HT2ML\FiscalBr\Models\Cfop;
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

it('renderiza a listagem de CFOPs', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCfop::class)
        ->assertOk();
});

it('cria um CFOP pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCfop::class)
        ->set('codigo', '5102')
        ->set('descricao', 'Venda de mercadoria adquirida de terceiros')
        ->set('tipo', 'saida')
        ->set('aplicacao', 'Operações de saída')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.cfops.index'));

    expect(Cfop::query()->where('codigo', '5102')->exists())->toBeTrue();
});

it('edita um CFOP existente', function () {
    $cfop = Cfop::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCfop::class, ['cfop' => $cfop->id])
        ->assertOk()
        ->assertSet('cfopId', $cfop->id)
        ->set('descricao', 'Descrição Atualizada')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($cfop->refresh()->descricao)->toBe('Descrição Atualizada');
});

it('move um CFOP para a lixeira e restaura', function () {
    $cfop = Cfop::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CfopTable::class)
        ->call('excluir', $cfop->id);

    expect(Cfop::onlyTrashed()->whereKey($cfop->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CfopTable::class)
        ->call('restaurar', $cfop->id);

    expect(Cfop::onlyTrashed()->whereKey($cfop->id)->exists())->toBeFalse()
        ->and(Cfop::whereKey($cfop->id)->exists())->toBeTrue();
});

it('exclui um CFOP definitivamente da lixeira', function () {
    $cfop = Cfop::factory()->create();
    $cfop->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CfopTable::class)
        ->call('excluirDefinitivo', $cfop->id);

    expect(Cfop::withTrashed()->whereKey($cfop->id)->exists())->toBeFalse();
});

it('exige permissão para listar CFOPs', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('cfops.listar'))->toBeFalse()
        ->and($this->admin->can('cfops.listar'))->toBeTrue();
});

it('abre a ficha Ver de CFOP pelo evento do kebab', function () {
    $registro = Cfop::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCfop::class)
        ->dispatch('cfops::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
