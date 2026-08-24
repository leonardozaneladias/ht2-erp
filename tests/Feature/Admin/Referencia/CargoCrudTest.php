<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\CargoTable;
use App\Livewire\Admin\Referencia\FormCargo;
use App\Livewire\Admin\Referencia\IndexCargo;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Cargo;
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

it('renderiza a listagem de Cargos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCargo::class)
        ->assertOk();
});

it('cria um Cargo pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCargo::class)
        ->set('codigo_cbo', '123456')
        ->set('descricao', 'Cargo de Teste')
        ->set('ativo', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.cargos.index'));

    expect(Cargo::query()->where('codigo_cbo', '123456')->exists())->toBeTrue();
});

it('edita um Cargo existente', function () {
    $cargo = Cargo::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCargo::class, ['cargo' => $cargo->id])
        ->assertOk()
        ->assertSet('cargoId', $cargo->id)
        ->set('descricao', 'Descrição Atualizada')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($cargo->refresh()->descricao)->toBe('Descrição Atualizada');
});

it('move um Cargo para a lixeira e restaura', function () {
    $cargo = Cargo::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CargoTable::class)
        ->call('excluir', $cargo->id);

    expect(Cargo::onlyTrashed()->whereKey($cargo->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CargoTable::class)
        ->call('restaurar', $cargo->id);

    expect(Cargo::onlyTrashed()->whereKey($cargo->id)->exists())->toBeFalse()
        ->and(Cargo::whereKey($cargo->id)->exists())->toBeTrue();
});

it('exclui um Cargo definitivamente da lixeira', function () {
    $cargo = Cargo::factory()->create();
    $cargo->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CargoTable::class)
        ->call('excluirDefinitivo', $cargo->id);

    expect(Cargo::withTrashed()->whereKey($cargo->id)->exists())->toBeFalse();
});

it('exige permissão para listar Cargos', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('cargos.listar'))->toBeFalse()
        ->and($this->admin->can('cargos.listar'))->toBeTrue();
});

it('abre a ficha Ver de cargo pelo evento do kebab', function () {
    $registro = Cargo::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCargo::class)
        ->dispatch('cargos::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
