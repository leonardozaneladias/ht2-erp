<?php

declare(strict_types=1);

use App\Livewire\Admin\Empresas\EmpresasTable;
use App\Models\Empresa;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin-empresas@teste.com');
    $this->admin->assignRole('super-admin');
});

it('filtra empresas por CNPJ (input_text, busca parcial nos dígitos)', function () {
    Empresa::create(['nome' => 'Alpha Servicos', 'cnpj' => '11222333000181', 'ativo' => true]);
    Empresa::create(['nome' => 'Beta Comercio', 'cnpj' => '99888777000166', 'ativo' => true]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(EmpresasTable::class)
        ->set('filters', ['input_text' => ['cnpj' => '11222333']])
        ->assertSee('Alpha Servicos')
        ->assertDontSee('Beta Comercio');
});
