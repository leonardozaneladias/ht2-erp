<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuracao\AbaEmpresa;
use App\Settings\GeneralSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('exibe a tela de configurações para quem tem permissão', function () {
    $admin = criarAdminUser('super@config.test');
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.configuracoes.index'))
        ->assertOk()
        ->assertSee('Configurações')
        ->assertSee('Dados da empresa');
});

it('nega acesso a quem não tem a permissão configuracoes.editar', function () {
    $semAcesso = criarAdminUser('semacesso@config.test');

    $this->actingAs($semAcesso, 'admin')
        ->get(route('admin.configuracoes.index'))
        ->assertForbidden();
});

it('salva os dados da empresa e grava activity log', function () {
    $admin = criarAdminUser('super@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaEmpresa::class)
        ->set('nome_cliente', 'Cliente Exemplo')
        ->set('razao_social', 'Cliente Exemplo Ltda')
        ->set('cnpj', '12.345.678/0001-90')
        ->set('email_contato', 'contato@exemplo.com')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $settings = app(GeneralSettings::class);
    expect($settings->nome_cliente)->toBe('Cliente Exemplo')
        ->and($settings->razao_social)->toBe('Cliente Exemplo Ltda')
        ->and($settings->email_contato)->toBe('contato@exemplo.com');

    expect(
        Activity::where('log_name', 'configuracoes')->where('event', 'updated')->exists(),
    )->toBeTrue();
});

it('exige o nome do cliente', function () {
    $admin = criarAdminUser('super@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaEmpresa::class)
        ->set('nome_cliente', '')
        ->call('salvar')
        ->assertHasErrors(['nome_cliente']);
});

it('rejeita e-mail de contato inválido', function () {
    $admin = criarAdminUser('super@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(AbaEmpresa::class)
        ->set('nome_cliente', 'X')
        ->set('email_contato', 'nao-eh-email')
        ->call('salvar')
        ->assertHasErrors(['email_contato']);
});

it('filtra as seções da navegação pela busca', function () {
    $admin = criarAdminUser('busca@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(App\Livewire\Admin\Configuracao\ConfiguracaoSistema::class)
        ->set('busca', 'fuso')
        ->assertSee('Localização')
        ->assertDontSee('Tela de login');
});

it('troca a aba ativa via irPara', function () {
    $admin = criarAdminUser('navega@config.test');
    $admin->assignRole('super-admin');

    Livewire::actingAs($admin, 'admin')
        ->test(App\Livewire\Admin\Configuracao\ConfiguracaoSistema::class)
        ->call('irPara', 'seguranca')
        ->assertSet('abaAtiva', 'seguranca')
        ->assertSee('Política de senha');
});
