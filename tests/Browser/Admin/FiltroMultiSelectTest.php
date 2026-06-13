<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
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

it('filtra a auditoria por um log via multi-select com busca', function () {
    $page = visit('/admin/auditoria');

    $page->assertSee('Registro do canal alpha')
        ->assertSee('Registro do canal beta');

    // O filtro de Log é o multi-select da coluna "Log" (TomSelect). Abrir e
    // selecionar a opção pelo data-value (deterministico — independe de qual
    // opção fica ativa por padrão).
    $page->click('.ts-control >> nth=1')
        ->click('.ts-dropdown [data-value="canalalpha"]')
        ->wait(1);

    $page->assertSee('Registro do canal alpha')
        ->assertDontSee('Registro do canal beta');
});

it('inicializa o TomSelect nos filtros multi-select da auditoria', function () {
    $page = visit('/admin/auditoria');

    // Super-admin enxerga 3 filtros multi-select (empresa, log, evento),
    // todos transformados em TomSelect (busca embutida).
    $controls = (int) $page->script("document.querySelectorAll('.ts-control').length");

    expect($controls)->toBe(3);
});
