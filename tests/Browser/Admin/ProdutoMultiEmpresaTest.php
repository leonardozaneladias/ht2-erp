<?php

declare(strict_types=1);

use App\Models\AdminUser;
use App\Models\Empresa;
use App\Models\Produto;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Smoke do filtro multi-empresa nas listagens: um super-admin com 2 empresas vê
 * a coluna Empresa e consegue incluir registros das duas pelo combobox, sem erro
 * de JS (o dropdown do multiselect usa dropdownParent: body para escapar do
 * overflow do PowerGrid).
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = AdminUser::create([
        'nome' => 'Super Admin Browser',
        'email' => 'multiempresa@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');
    $this->actingAs($admin, 'admin');

    $this->alpha = Empresa::factory()->create(['nome' => 'Empresa Alpha', 'ativo' => true]);
    $this->beta = Empresa::factory()->create(['nome' => 'Empresa Beta', 'ativo' => true]);

    $ctx = app(TenantContext::class);
    $ctx->definirEmpresa($this->alpha->id);
    Produto::factory()->create(['nome' => 'Produto Alpha']);
    $ctx->definirEmpresa($this->beta->id);
    Produto::factory()->create(['nome' => 'Produto Beta']);
});

it('mostra a coluna Empresa e inclui registros de 2 empresas pelo combobox', function () {
    $page = visit('/admin/produtos');

    // Multi-empresa ativo (super-admin + 2 empresas) → coluna Empresa presente.
    $page->assertSee('Empresa');

    // Inclui as duas empresas no filtro de empresa (multiselect = combobox custom).
    $page->click('[data-testid="combobox-trigger"][data-combobox-field="empresa_id"]')
        ->click('[data-testid="combobox-panel"][data-combobox-field="empresa_id"] [data-value="' . $this->alpha->id . '"]')
        ->click('[data-testid="combobox-panel"][data-combobox-field="empresa_id"] [data-value="' . $this->beta->id . '"]')
        ->wait(1);

    $page->assertSee('Produto Alpha')
        ->assertSee('Produto Beta')
        ->assertSee('Empresa Alpha')
        ->assertSee('Empresa Beta')
        ->assertNoJavaScriptErrors();
});
