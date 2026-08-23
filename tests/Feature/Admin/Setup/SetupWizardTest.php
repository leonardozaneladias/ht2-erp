<?php

declare(strict_types=1);

use App\Livewire\Admin\Setup\SetupWizard;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Settings\BrandingSettings;
use HT2ML\Core\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Cria as roles (incl. super-admin) e força o estado "não instalado",
    // sobrescrevendo o padrão global definido em tests/Pest.php.
    $this->seed(RolePermissionSeeder::class);
    marcarInstalado(false);
});

it('redireciona o painel para o setup enquanto não instalado', function () {
    // Rota pública de autenticação encaminha direto para o assistente.
    $this->get(route('admin.login'))->assertRedirect(route('admin.setup'));

    // Usuário autenticado também é levado ao assistente.
    $admin = criarAdminUser('pendente@setup.test');
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.setup'));
});

it('exibe o assistente de configuração', function () {
    $this->get(route('admin.setup'))
        ->assertOk()
        ->assertSee('Configuração inicial');
});

it('conclui a instalação criando o super-admin e marcando instalado', function () {
    Livewire::test(SetupWizard::class)
        ->set('nome_cliente', 'Cliente Acme')
        ->set('cnpj', '12.345.678/0001-90')
        ->call('proximo')
        ->assertSet('passo', 2)
        ->set('nome_sistema', 'ERP Acme')
        ->set('cor_primaria', '#3366ff')
        ->call('proximo')
        ->assertSet('passo', 3)
        ->set('admin_nome', 'Dono Acme')
        ->set('admin_email', 'dono@acme.com')
        ->set('admin_senha', 'SenhaForte1')
        ->call('concluir')
        ->assertRedirect(route('admin.login'));

    expect(app(GeneralSettings::class)->instalado)->toBeTrue()
        ->and(app(GeneralSettings::class)->nome_cliente)->toBe('Cliente Acme')
        ->and(app(BrandingSettings::class)->nome_sistema)->toBe('ERP Acme');

    $admin = AdminUser::where('email', 'dono@acme.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('super-admin'))->toBeTrue();

    // A instalação cria a 1ª empresa (com Matriz) e vincula o super-admin a ela.
    $empresa = Empresa::query()->where('nome', 'Cliente Acme')->first();

    expect($empresa)->not->toBeNull()
        ->and($empresa->filiais()->where('e_matriz', true)->exists())->toBeTrue()
        ->and($admin->temAcessoAEmpresa($empresa->id))->toBeTrue()
        ->and($admin->fresh()->empresa_ativa_id)->toBe($empresa->id);
});

it('redireciona para o login se a instalação já foi concluída', function () {
    $general = app(GeneralSettings::class);
    $general->instalado = true;
    $general->save();

    Livewire::test(SetupWizard::class)->assertRedirect(route('admin.login'));
});

it('valida campos obrigatórios antes de avançar', function () {
    Livewire::test(SetupWizard::class)
        ->set('nome_cliente', '')
        ->call('proximo')
        ->assertHasErrors(['nome_cliente'])
        ->assertSet('passo', 1);
});
