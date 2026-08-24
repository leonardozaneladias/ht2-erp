<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\FormPais;
use App\Livewire\Admin\Referencia\IndexPais;
use App\Livewire\Admin\Referencia\PaisTable;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Pais;
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

it('renderiza a listagem de Países', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexPais::class)
        ->assertOk();
});

it('cria um País pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormPais::class)
        ->set('codigo_iso2', 'ZZ')
        ->set('codigo_iso3', 'ZZZ')
        ->set('codigo_numerico', '999')
        ->set('nome', 'País de Teste')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.paises.index'));

    expect(Pais::query()->where('codigo_iso2', 'ZZ')->exists())->toBeTrue();
});

it('edita um País existente', function () {
    $pais = Pais::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormPais::class, ['pais' => $pais->id])
        ->assertOk()
        ->assertSet('paisId', $pais->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($pais->refresh()->nome)->toBe('Nome Atualizado');
});

it('move um País para a lixeira e restaura', function () {
    $pais = Pais::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PaisTable::class)
        ->call('excluir', $pais->id);

    expect(Pais::onlyTrashed()->whereKey($pais->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PaisTable::class)
        ->call('restaurar', $pais->id);

    expect(Pais::onlyTrashed()->whereKey($pais->id)->exists())->toBeFalse()
        ->and(Pais::whereKey($pais->id)->exists())->toBeTrue();
});

it('exclui um País definitivamente da lixeira', function () {
    $pais = Pais::factory()->create();
    $pais->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PaisTable::class)
        ->call('excluirDefinitivo', $pais->id);

    expect(Pais::withTrashed()->whereKey($pais->id)->exists())->toBeFalse();
});

it('exige permissão para listar Países', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('paises.listar'))->toBeFalse()
        ->and($this->admin->can('paises.listar'))->toBeTrue();
});

it('abre a ficha Ver de país pelo evento do kebab', function () {
    $registro = Pais::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexPais::class)
        ->dispatch('paises::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
