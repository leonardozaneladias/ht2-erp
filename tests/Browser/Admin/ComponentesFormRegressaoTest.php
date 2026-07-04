<?php

declare(strict_types=1);

use App\Models\AdminUser;
use App\Models\Empresa;
use App\Models\Exemplo;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

/*
 * Regressão dos defeitos sistêmicos de componente de formulário (upstream do
 * laudo 18 do módulo RH): F9-01 (date-picker re-parseava o ISO hidratado como
 * d/m/Y — data exibida errada e mês/dia trocados na digitação) e F9-02
 * (select-search nunca exibia o valor salvo na edição). Cobre também o espaço
 * do ícone no altInput do flatpickr: o clone visível não é irmão adjacente do
 * ícone e ainda assim precisa do ps-10 (senão o placeholder fica por baixo).
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);

    $admin = AdminUser::create([
        'nome' => 'Regressão Componentes',
        'email' => 'regressao-componentes@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
        'empresa_ativa_id' => $this->empresa->id,
    ]);
    $admin->assignRole('super-admin');
    $this->actingAs($admin, 'admin');
});

it('date-picker exibe a data persistida em d/m/Y e mantém o ISO no campo (F9-01)', function () {
    $exemplo = Exemplo::factory()->create(['data_inicio' => '2026-03-10']);

    $page = visit('/admin/exemplos/' . $exemplo->id . '/editar');

    $page->assertNoJavaScriptErrors()
        ->wait(1)
        // O input original (wire:model) guarda o valor canônico ISO…
        ->assertValue('[name="data_inicio"]', '2026-03-10')
        // …e o altInput do flatpickr (irmão seguinte) exibe d/m/Y correto.
        ->assertScript(
            "document.querySelector('[name=\"data_inicio\"]').nextElementSibling.value",
            '10/03/2026',
        );
});

it('o input visível do date-picker reserva o espaço do ícone', function () {
    $exemplo = Exemplo::factory()->create(['data_inicio' => '2026-03-10']);

    $page = visit('/admin/exemplos/' . $exemplo->id . '/editar');
    $page->wait(1);

    // ps-10 = 2.5rem = 40px; sem ele o placeholder sobrepõe o ícone do calendário.
    $padding = (float) $page->script(
        "parseFloat(getComputedStyle(document.querySelector('[name=\"data_inicio\"]').nextElementSibling).paddingLeft)",
    );

    expect($padding)->toBeGreaterThanOrEqual(39.0);
});

it('select-search exibe o valor salvo no resumo do combobox (F9-02)', function () {
    $exemplo = Exemplo::factory()->create(['categoria' => 'produto']);

    $page = visit('/admin/exemplos/' . $exemplo->id . '/editar');

    $page->assertNoJavaScriptErrors()
        ->wait(1)
        ->assertScript(
            "Array.from(document.querySelectorAll('[data-testid=\"combobox-trigger\"]')).some((t) => t.innerText.includes('Produto'))",
            true,
        );
});
