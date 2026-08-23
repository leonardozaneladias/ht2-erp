<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\FiscalBr\Livewire\CnaeTable;
use HT2ML\FiscalBr\Livewire\FormCnae;
use HT2ML\FiscalBr\Livewire\IndexCnae;
use HT2ML\FiscalBr\Models\Cnae;
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

it('renderiza a listagem de CNAEs', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCnae::class)
        ->assertOk();
});

it('cria um CNAE pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCnae::class)
        ->set('codigo', '9999999')
        ->set('descricao', 'CNAE de Teste')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.cnaes.index'));

    expect(Cnae::query()->where('codigo', '9999999')->exists())->toBeTrue();
});

it('edita um CNAE existente', function () {
    $cnae = Cnae::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormCnae::class, ['cnae' => $cnae->id])
        ->assertOk()
        ->assertSet('cnaeId', $cnae->id)
        ->set('descricao', 'Descrição Atualizada')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($cnae->refresh()->descricao)->toBe('Descrição Atualizada');
});

it('move um CNAE para a lixeira e restaura', function () {
    $cnae = Cnae::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CnaeTable::class)
        ->call('excluir', $cnae->id);

    expect(Cnae::onlyTrashed()->whereKey($cnae->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CnaeTable::class)
        ->call('restaurar', $cnae->id);

    expect(Cnae::onlyTrashed()->whereKey($cnae->id)->exists())->toBeFalse()
        ->and(Cnae::whereKey($cnae->id)->exists())->toBeTrue();
});

it('exclui um CNAE definitivamente da lixeira', function () {
    $cnae = Cnae::factory()->create();
    $cnae->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(CnaeTable::class)
        ->call('excluirDefinitivo', $cnae->id);

    expect(Cnae::withTrashed()->whereKey($cnae->id)->exists())->toBeFalse();
});

it('exige permissão para listar CNAEs', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('cnaes.listar'))->toBeFalse()
        ->and($this->admin->can('cnaes.listar'))->toBeTrue();
});

it('abre a ficha Ver de CNAE pelo evento do kebab', function () {
    $registro = Cnae::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexCnae::class)
        ->dispatch('cnaes::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir');
});
