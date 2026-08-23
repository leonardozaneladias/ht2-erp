<?php

declare(strict_types=1);

use Database\Seeders\Referencia\CargoSeeder;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\Rh\Livewire\Funcionarios\FormFuncionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('Funcionário: o select de cargo lista o catálogo', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CargoSeeder::class);

    $empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($empresa->id);

    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');

    $component = Livewire::actingAs($super, 'admin')->test(FormFuncionario::class);

    expect($component->instance()->cargosDisponiveis)->toHaveKey('Administrador');
});
