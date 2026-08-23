<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\FormMunicipio;
use App\Livewire\Admin\Referencia\IndexMunicipio;
use App\Livewire\Admin\Referencia\MunicipioTable;
use App\Models\Referencia\Estado;
use App\Models\Referencia\Municipio;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
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

it('renderiza a listagem de Municípios', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexMunicipio::class)
        ->assertOk();
});

it('cria um Município pelo formulário', function () {
    $estado = Estado::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormMunicipio::class)
        ->set('codigo_ibge', '9999999')
        ->set('nome', 'Município de Teste')
        ->set('estado_id', $estado->id)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.municipios.index'));

    expect(Municipio::query()->where('codigo_ibge', '9999999')->exists())->toBeTrue();
});

it('edita um Município existente', function () {
    $municipio = Municipio::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormMunicipio::class, ['municipio' => $municipio->id])
        ->assertOk()
        ->assertSet('municipioId', $municipio->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($municipio->refresh()->nome)->toBe('Nome Atualizado');
});

it('move um Município para a lixeira e restaura', function () {
    $municipio = Municipio::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MunicipioTable::class)
        ->call('excluir', $municipio->id);

    expect(Municipio::onlyTrashed()->whereKey($municipio->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MunicipioTable::class)
        ->call('restaurar', $municipio->id);

    expect(Municipio::onlyTrashed()->whereKey($municipio->id)->exists())->toBeFalse()
        ->and(Municipio::whereKey($municipio->id)->exists())->toBeTrue();
});

it('exclui um Município definitivamente da lixeira', function () {
    $municipio = Municipio::factory()->create();
    $municipio->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MunicipioTable::class)
        ->call('excluirDefinitivo', $municipio->id);

    expect(Municipio::withTrashed()->whereKey($municipio->id)->exists())->toBeFalse();
});

it('exige permissão para listar Municípios', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('municipios.listar'))->toBeFalse()
        ->and($this->admin->can('municipios.listar'))->toBeTrue();
});

it('abre a ficha Ver de município pelo evento do kebab', function () {
    $registro = Municipio::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexMunicipio::class)
        ->dispatch('municipios::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
