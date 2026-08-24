<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

/**
 * Regressão do z-index dos dropdowns do header do PowerGrid: eles abrem
 * sobre a linha de filtros (thead sticky). Se voltarem a ficar por trás,
 * o clique nos itens falha por interceptação de pointer do Playwright.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = AdminUser::create([
        'nome' => 'Super Admin Browser',
        'email' => 'dropdowns@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin');
});

it('abre o seletor de colunas sobre os filtros e alterna uma coluna', function () {
    $page = visit('/admin/usuarios');

    $page->assertSee('dropdowns@teste.com')
        ->click('[data-cy="toggle-columns-usuarios-table"]')
        ->assertVisible('.toggle-columns-base');

    // Regra do fix: o dropdown precisa empilhar ACIMA do thead sticky
    // (.table-sticky th = z-10). Comparar o z-index computado é determinístico
    // e falha se o override voltar ao z-10 do vendor (empate com o thead).
    $zDropdown = (int) $page->script("getComputedStyle(document.querySelector('.toggle-columns-base')).zIndex");
    $zThead = (int) $page->script("getComputedStyle(document.querySelector('table thead th')).zIndex");

    expect($zDropdown)->toBeGreaterThan($zThead);

    $page->click('[data-cy="toggle-field-email"]')
        ->wait(1)
        ->assertDontSee('dropdowns@teste.com');
});

it('abre o menu de exportação sobre os filtros', function () {
    $page = visit('/admin/usuarios');

    $page->click('#pg-header-export > div > button')
        ->assertSee('XLSX');

    // Mesma regra do seletor de colunas: o menu de export empilha acima do
    // thead sticky (z-10). Falha se o override voltar ao z-10 do vendor.
    $zMenu = (int) $page->script("getComputedStyle(document.querySelector('#pg-header-export .absolute')).zIndex");
    $zThead = (int) $page->script("getComputedStyle(document.querySelector('table thead th')).zIndex");

    expect($zMenu)->toBeGreaterThan($zThead);
});
