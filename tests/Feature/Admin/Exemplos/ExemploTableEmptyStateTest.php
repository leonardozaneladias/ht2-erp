<?php

declare(strict_types=1);

use App\Livewire\Admin\Exemplos\ExemploTable;
use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);
});

// O Exemplo é a referência viva do gerador: o noDataLabel() aqui espelha o que o
// stub livewire-table.stub passa a gerar para todo módulo novo. Estes testes
// garantem que as listagens geradas usem o empty-state do projeto (powergrid-empty,
// PT-BR + CTA), e não o estado vazio genérico default do PowerGrid.

it('usa o empty-state do projeto (powergrid-empty) no noDataLabel', function () {
    $html = (new ExemploTable)->noDataLabel()->render();

    expect($html)
        ->toContain('Nenhum registro encontrado')
        ->toContain('Cadastre o primeiro registro para começar.');
});

it('omite o CTA de criação quando o usuário não pode criar', function () {
    // Sem usuário autenticado, podeCriar = false → nenhum botão "Novo registro".
    $html = (new ExemploTable)->noDataLabel()->render();

    expect($html)->not->toContain('Novo registro');
});

it('mostra o CTA de criação para usuário autorizado', function () {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $this->actingAs($super, 'admin');

    $html = (new ExemploTable)->noDataLabel()->render();

    expect($html)
        ->toContain('Novo registro')
        ->toContain(route('admin.exemplos.create'));
});
