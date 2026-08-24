<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Empresas\EmpresasTable;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');
});

it('exclui uma empresa movendo-a para a lixeira', function () {
    $ativa = Empresa::factory()->create();
    $alvo = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($ativa->id);

    Livewire::actingAs($this->super, 'admin')->test(EmpresasTable::class)
        ->call('excluir', $alvo->id)
        ->assertHasNoErrors();

    expect(Empresa::query()->whereKey($alvo->id)->exists())->toBeFalse()
        ->and(Empresa::withTrashed()->find($alvo->id)->trashed())->toBeTrue();
});

it('guarda D1: bloqueia excluir a empresa ativa', function () {
    $ativa = Empresa::factory()->create();
    Empresa::factory()->create(); // garante que não é a última
    app(TenantContext::class)->definirEmpresa($ativa->id);

    Livewire::actingAs($this->super, 'admin')->test(EmpresasTable::class)
        ->call('excluir', $ativa->id);

    expect(Empresa::query()->whereKey($ativa->id)->exists())->toBeTrue();
});

it('guarda D1: bloqueia excluir a última empresa', function () {
    Empresa::query()->forceDelete();
    $unica = Empresa::factory()->create();

    Livewire::actingAs($this->super, 'admin')->test(EmpresasTable::class)
        ->call('excluir', $unica->id);

    expect(Empresa::query()->whereKey($unica->id)->exists())->toBeTrue();
});

it('restaura uma empresa da lixeira', function () {
    $registro = Empresa::factory()->trashed()->create();

    Livewire::actingAs($this->super, 'admin')->test(EmpresasTable::class)
        ->call('restaurar', $registro->id)
        ->assertHasNoErrors();

    expect(Empresa::query()->whereKey($registro->id)->exists())->toBeTrue();
});

it('exclui definitivamente uma empresa e cascateia (físico) para as filiais', function () {
    $registro = Empresa::factory()->trashed()->create();
    $filial = Filial::factory()->create(['empresa_id' => $registro->id]);

    Livewire::actingAs($this->super, 'admin')->test(EmpresasTable::class)
        ->call('excluirDefinitivo', $registro->id)
        ->assertHasNoErrors();

    expect(Empresa::withTrashed()->whereKey($registro->id)->exists())->toBeFalse()
        ->and(Filial::withTrashed()->whereKey($filial->id)->exists())->toBeFalse();
});

it('nega as ações de lixeira a quem não tem permissão (403)', function () {
    $role = Role::findOrCreate('operador-empresas', 'admin');
    $role->givePermissionTo(Permission::findOrCreate('empresas.listar', 'admin'));
    $limitado = criarAdminUser('op@teste.com');
    $limitado->assignRole('operador-empresas');

    $ativa = Empresa::factory()->create();
    $alvo = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($ativa->id);
    $naLixeira = Empresa::factory()->trashed()->create();

    Livewire::actingAs($limitado, 'admin')->test(EmpresasTable::class)
        ->call('excluir', $alvo->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(EmpresasTable::class)
        ->call('restaurar', $naLixeira->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(EmpresasTable::class)
        ->call('excluirDefinitivo', $naLixeira->id)->assertForbidden();
});
