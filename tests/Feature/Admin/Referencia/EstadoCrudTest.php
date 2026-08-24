<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Referencia\EstadoTable;
use HT2ML\Core\Livewire\Admin\Referencia\FormEstado;
use HT2ML\Core\Livewire\Admin\Referencia\IndexEstado;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Estado;
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

it('renderiza a listagem de Estados', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexEstado::class)
        ->assertOk();
});

it('cria um Estado pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEstado::class)
        ->set('codigo_ibge', '99')
        ->set('sigla', 'ZZ')
        ->set('nome', 'Estado de Teste')
        ->set('regiao', 'sudeste')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.estados.index'));

    expect(Estado::query()->where('codigo_ibge', '99')->exists())->toBeTrue();
});

it('edita um Estado existente', function () {
    $estado = Estado::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEstado::class, ['estado' => $estado->id])
        ->assertOk()
        ->assertSet('estadoId', $estado->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($estado->refresh()->nome)->toBe('Nome Atualizado');
});

it('move um Estado para a lixeira e restaura', function () {
    $estado = Estado::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(EstadoTable::class)
        ->call('excluir', $estado->id);

    expect(Estado::onlyTrashed()->whereKey($estado->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(EstadoTable::class)
        ->call('restaurar', $estado->id);

    expect(Estado::onlyTrashed()->whereKey($estado->id)->exists())->toBeFalse()
        ->and(Estado::whereKey($estado->id)->exists())->toBeTrue();
});

it('exclui um Estado definitivamente da lixeira', function () {
    $estado = Estado::factory()->create();
    $estado->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(EstadoTable::class)
        ->call('excluirDefinitivo', $estado->id);

    expect(Estado::withTrashed()->whereKey($estado->id)->exists())->toBeFalse();
});

it('exige permissão para listar Estados', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('estados.listar'))->toBeFalse()
        ->and($this->admin->can('estados.listar'))->toBeTrue();
});

it('abre a ficha Ver de estado pelo evento do kebab', function () {
    $registro = Estado::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexEstado::class)
        ->dispatch('estados::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
