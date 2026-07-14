<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\FormTipoLogradouro;
use App\Livewire\Admin\Referencia\IndexTipoLogradouro;
use App\Livewire\Admin\Referencia\TipoLogradouroTable;
use App\Models\AdminUser;
use App\Models\Referencia\TipoLogradouro;
use Database\Seeders\RolePermissionSeeder;
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

it('renderiza a listagem de Tipos de logradouro', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexTipoLogradouro::class)
        ->assertOk();
});

it('cria um Tipo de logradouro pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormTipoLogradouro::class)
        ->set('nome', 'Viaduto')
        ->set('codigo', '99')
        ->set('abrev', 'Vdt')
        ->set('ativo', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.tipos_logradouro.index'));

    expect(TipoLogradouro::query()->where('nome', 'Viaduto')->exists())->toBeTrue();
});

it('edita um Tipo de logradouro existente', function () {
    $tipoLogradouro = TipoLogradouro::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormTipoLogradouro::class, ['tipo_logradouro' => $tipoLogradouro->id])
        ->assertOk()
        ->assertSet('tipoLogradouroId', $tipoLogradouro->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($tipoLogradouro->refresh()->nome)->toBe('Nome Atualizado');
});

it('move um Tipo de logradouro para a lixeira e restaura', function () {
    $tipoLogradouro = TipoLogradouro::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(TipoLogradouroTable::class)
        ->call('excluir', $tipoLogradouro->id);

    expect(TipoLogradouro::onlyTrashed()->whereKey($tipoLogradouro->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(TipoLogradouroTable::class)
        ->call('restaurar', $tipoLogradouro->id);

    expect(TipoLogradouro::onlyTrashed()->whereKey($tipoLogradouro->id)->exists())->toBeFalse()
        ->and(TipoLogradouro::whereKey($tipoLogradouro->id)->exists())->toBeTrue();
});

it('exclui um Tipo de logradouro definitivamente da lixeira', function () {
    $tipoLogradouro = TipoLogradouro::factory()->create();
    $tipoLogradouro->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(TipoLogradouroTable::class)
        ->call('excluirDefinitivo', $tipoLogradouro->id);

    expect(TipoLogradouro::withTrashed()->whereKey($tipoLogradouro->id)->exists())->toBeFalse();
});

it('exige permissão para listar Tipos de logradouro', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('tipos_logradouro.listar'))->toBeFalse()
        ->and($this->admin->can('tipos_logradouro.listar'))->toBeTrue();
});

it('abre a ficha Ver de tipo de logradouro pelo evento do kebab', function () {
    $registro = TipoLogradouro::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexTipoLogradouro::class)
        ->dispatch('tipos_logradouro::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
