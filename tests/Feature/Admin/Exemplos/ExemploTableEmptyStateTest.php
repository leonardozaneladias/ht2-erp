<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use HT2ML\ExemploDemo\Livewire\Exemplos\ExemploTable;
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
