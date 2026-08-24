<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\FormMoeda;
use App\Livewire\Admin\Referencia\IndexMoeda;
use App\Livewire\Admin\Referencia\MoedaTable;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Moeda;
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

it('renderiza a listagem de Moedas', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexMoeda::class)
        ->assertOk();
});

it('cria uma Moeda pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormMoeda::class)
        ->set('codigo_iso', 'ZZZ')
        ->set('numerico', '999')
        ->set('nome', 'Moeda de Teste')
        ->set('simbolo', '$')
        ->set('casas_decimais', 2)
        ->set('ativo', true)
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.moedas.index'));

    expect(Moeda::query()->where('codigo_iso', 'ZZZ')->exists())->toBeTrue();
});

it('edita uma Moeda existente', function () {
    $moeda = Moeda::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormMoeda::class, ['moeda' => $moeda->id])
        ->assertOk()
        ->assertSet('moedaId', $moeda->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($moeda->refresh()->nome)->toBe('Nome Atualizado');
});

it('move uma Moeda para a lixeira e restaura', function () {
    $moeda = Moeda::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MoedaTable::class)
        ->call('excluir', $moeda->id);

    expect(Moeda::onlyTrashed()->whereKey($moeda->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MoedaTable::class)
        ->call('restaurar', $moeda->id);

    expect(Moeda::onlyTrashed()->whereKey($moeda->id)->exists())->toBeFalse()
        ->and(Moeda::whereKey($moeda->id)->exists())->toBeTrue();
});

it('exclui uma Moeda definitivamente da lixeira', function () {
    $moeda = Moeda::factory()->create();
    $moeda->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(MoedaTable::class)
        ->call('excluirDefinitivo', $moeda->id);

    expect(Moeda::withTrashed()->whereKey($moeda->id)->exists())->toBeFalse();
});

it('exige permissão para listar Moedas', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('moedas.listar'))->toBeFalse()
        ->and($this->admin->can('moedas.listar'))->toBeTrue();
});

it('abre a ficha Ver de moeda pelo evento do kebab', function () {
    $registro = Moeda::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexMoeda::class)
        ->dispatch('moedas::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
