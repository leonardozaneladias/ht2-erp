<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = AdminUser::create([
        'nome' => 'Super Admin Browser',
        'email' => 'multiselect@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin');

    activity('canalalpha')->log('Registro do canal alpha');
    activity('canalbeta')->log('Registro do canal beta');
});

it('filtra a auditoria por um log via combobox (gatilho + opção)', function () {
    $page = visit('/admin/auditoria');

    $page->assertSee('Registro do canal alpha')
        ->assertSee('Registro do canal beta');

    // Abre o combobox da coluna "Log" e marca a opção pelo data-value
    // (deterministico). Os seletores sao escopados por data-combobox-field
    // porque ha 3 comboboxes na tela (empresa, log, evento).
    $page->click('[data-testid="combobox-trigger"][data-combobox-field="log_name"]')
        ->click('[data-testid="combobox-panel"][data-combobox-field="log_name"] [data-value="canalalpha"]')
        ->wait(1);

    $page->assertSee('Registro do canal alpha')
        ->assertDontSee('Registro do canal beta');
});

it('filtra usando o campo de busca do combobox', function () {
    $page = visit('/admin/auditoria');

    $page->click('[data-testid="combobox-trigger"][data-combobox-field="log_name"]')
        ->fill('[data-testid="combobox-panel"][data-combobox-field="log_name"] [data-testid="combobox-search"]', 'alpha')
        ->wait(1)
        // Apos buscar "alpha", so a opcao canalalpha permanece na lista.
        ->click('[data-testid="combobox-panel"][data-combobox-field="log_name"] [data-value="canalalpha"]')
        ->wait(1);

    $page->assertSee('Registro do canal alpha')
        ->assertDontSee('Registro do canal beta');
});

it('inicializa os 3 filtros combobox da auditoria', function () {
    $page = visit('/admin/auditoria');

    // Super-admin enxerga 3 filtros multi-select (empresa, log, evento),
    // todos renderizados como combobox custom (busca + checkbox).
    $controls = (int) $page->script('document.querySelectorAll(\'[data-testid="combobox-trigger"]\').length');

    expect($controls)->toBe(3);
});
