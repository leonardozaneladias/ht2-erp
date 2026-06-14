<?php

declare(strict_types=1);

use App\Models\AdminUser;
use App\Models\Empresa;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = AdminUser::create([
        'nome' => 'Super Empresas',
        'email' => 'empresas-form@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');
    $this->actingAs($admin, 'admin');
});

it('aplica a máscara de CNPJ no formulário de empresa', function () {
    $page = visit('/admin/empresas/nova');
    $page->assertNoJavaScriptErrors()->wait(1);

    // A máscara foi inicializada no campo (Inputmask marca o elemento).
    $bound = (int) $page->script(
        'document.querySelectorAll(\'input[name="cnpj"][data-af-forms-inputmask-bound="true"]\').length',
    );
    expect($bound)->toBe(1);

    // Digitar dígitos resulta no valor formatado pela máscara.
    $page->type('input[name="cnpj"]', '11222333000181')->wait(1);
    $valor = (string) $page->script('document.querySelector(\'input[name="cnpj"]\').value');
    expect($valor)->toBe('11.222.333/0001-81');
});

it('filtra empresas pelo status via combobox pesquisável', function () {
    Empresa::create(['nome' => 'Alpha Ativa Ltda', 'ativo' => true]);
    Empresa::create(['nome' => 'Beta Inativa Ltda', 'ativo' => false]);

    $page = visit('/admin/empresas');
    $page->assertSee('Alpha Ativa Ltda')
        ->assertSee('Beta Inativa Ltda');

    // O filtro de status agora é o combobox (não <select> nativo). Abre pelo
    // gatilho (aria-controls aponta para o painel determinístico) e escolhe "não".
    $page->click('[aria-controls="filtro-boolean-empresas-table-ativo-panel"]')
        ->click('#filtro-boolean-empresas-table-ativo-panel [data-value="false"]')
        ->wait(1);

    // Escopado ao corpo da tabela: "Alpha Ativa" também aparece no topbar
    // (empresa ativa), então asserções de página inteira dariam falso-positivo.
    $corpo = (string) $page->script("document.querySelector('table tbody').textContent");
    expect($corpo)->toContain('Beta Inativa Ltda')
        ->not->toContain('Alpha Ativa Ltda');
});
