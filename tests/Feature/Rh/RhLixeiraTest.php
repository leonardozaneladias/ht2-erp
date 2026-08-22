<?php

declare(strict_types=1);

use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Rh\Livewire\Departamentos\DepartamentoTable;
use HT2ML\Rh\Livewire\Funcionarios\FuncionarioTable;
use HT2ML\Rh\Models\Departamento;
use HT2ML\Rh\Models\Funcionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);
    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');
});

it('Departamento: move para a lixeira, restaura e exclui definitivamente', function () {
    $registro = Departamento::factory()->create();

    Livewire::actingAs($this->super, 'admin')->test(DepartamentoTable::class)
        ->call('excluir', $registro->id)->assertHasNoErrors();
    expect(Departamento::query()->whereKey($registro->id)->exists())->toBeFalse()
        ->and(Departamento::withTrashed()->find($registro->id)->trashed())->toBeTrue();

    Livewire::actingAs($this->super, 'admin')->test(DepartamentoTable::class)
        ->call('restaurar', $registro->id)->assertHasNoErrors();
    expect(Departamento::query()->whereKey($registro->id)->exists())->toBeTrue();

    Departamento::query()->findOrFail($registro->id)->delete();
    Livewire::actingAs($this->super, 'admin')->test(DepartamentoTable::class)
        ->call('excluirDefinitivo', $registro->id)->assertHasNoErrors();
    expect(Departamento::withTrashed()->whereKey($registro->id)->exists())->toBeFalse();
});

it('Funcionario: move para a lixeira, restaura e exclui definitivamente', function () {
    $registro = Funcionario::factory()->create();

    Livewire::actingAs($this->super, 'admin')->test(FuncionarioTable::class)
        ->call('excluir', $registro->id)->assertHasNoErrors();
    expect(Funcionario::query()->whereKey($registro->id)->exists())->toBeFalse()
        ->and(Funcionario::withTrashed()->find($registro->id)->trashed())->toBeTrue();

    Livewire::actingAs($this->super, 'admin')->test(FuncionarioTable::class)
        ->call('restaurar', $registro->id)->assertHasNoErrors();
    expect(Funcionario::query()->whereKey($registro->id)->exists())->toBeTrue();

    Funcionario::query()->findOrFail($registro->id)->delete();
    Livewire::actingAs($this->super, 'admin')->test(FuncionarioTable::class)
        ->call('excluirDefinitivo', $registro->id)->assertHasNoErrors();
    expect(Funcionario::withTrashed()->whereKey($registro->id)->exists())->toBeFalse();
});

it('nega as ações de lixeira do RH a quem só tem listar (403)', function () {
    $role = Role::findOrCreate('operador-rh', 'admin');
    $role->givePermissionTo(Permission::findOrCreate('rh.departamentos.listar', 'admin'));
    $role->givePermissionTo(Permission::findOrCreate('rh.funcionarios.listar', 'admin'));
    $limitado = criarAdminUser('op@teste.com');
    $limitado->assignRole('operador-rh');

    $depAtivo = Departamento::factory()->create();
    $depLixeira = Departamento::factory()->trashed()->create();
    $funcLixeira = Funcionario::factory()->trashed()->create();

    Livewire::actingAs($limitado, 'admin')->test(DepartamentoTable::class)
        ->call('excluir', $depAtivo->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(DepartamentoTable::class)
        ->call('restaurar', $depLixeira->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(FuncionarioTable::class)
        ->call('excluirDefinitivo', $funcLixeira->id)->assertForbidden();
});
