<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\BancoTable;
use App\Livewire\Admin\Referencia\FormBanco;
use App\Livewire\Admin\Referencia\IndexBanco;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Referencia\Banco;
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

it('renderiza a listagem de Bancos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexBanco::class)
        ->assertOk();
});

it('cria um Banco pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormBanco::class)
        ->set('ispb', '12345678')
        ->set('codigo_compe', '001')
        ->set('nome', 'Banco de Teste')
        ->set('nome_completo', 'Banco de Teste S.A.')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.bancos.index'));

    expect(Banco::query()->where('ispb', '12345678')->exists())->toBeTrue();
});

it('edita um Banco existente', function () {
    $banco = Banco::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormBanco::class, ['banco' => $banco->id])
        ->assertOk()
        ->assertSet('bancoId', $banco->id)
        ->set('nome', 'Nome Atualizado')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($banco->refresh()->nome)->toBe('Nome Atualizado');
});

it('move um Banco para a lixeira e restaura', function () {
    $banco = Banco::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(BancoTable::class)
        ->call('excluir', $banco->id);

    expect(Banco::onlyTrashed()->whereKey($banco->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(BancoTable::class)
        ->call('restaurar', $banco->id);

    expect(Banco::onlyTrashed()->whereKey($banco->id)->exists())->toBeFalse()
        ->and(Banco::whereKey($banco->id)->exists())->toBeTrue();
});

it('exclui um Banco definitivamente da lixeira', function () {
    $banco = Banco::factory()->create();
    $banco->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(BancoTable::class)
        ->call('excluirDefinitivo', $banco->id);

    expect(Banco::withTrashed()->whereKey($banco->id)->exists())->toBeFalse();
});

it('exige permissão para listar Bancos', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('bancos.listar'))->toBeFalse()
        ->and($this->admin->can('bancos.listar'))->toBeTrue();
});

it('abre a ficha Ver de banco pelo evento do kebab', function () {
    $registro = Banco::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexBanco::class)
        ->dispatch('bancos::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
