<?php

declare(strict_types=1);

use App\Actions\Admin\Menu\AplicarMenuPadraoAction;
use App\Enums\TipoPersonalizacaoMenu;
use App\Models\MenuPersonalizacao;
use App\Services\Admin\Menu\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('aplica a disposição padrão com os grupos Cadastros e Segurança', function () {
    expect(app(AplicarMenuPadraoAction::class)->execute())->toBeTrue();

    $secoes = app(MenuService::class)->estruturaParaSidebar(null, mostrarTudo: true);
    $administracao = collect($secoes)->firstWhere('key', 'administracao');
    $porKey = collect($administracao['items'])->keyBy('key');

    expect(array_column($administracao['items'], 'key'))
        ->toBe(['grupo-cadastros', 'grupo-seguranca', 'auditoria', 'comunicados'])
        ->and(array_column($porKey['grupo-cadastros']['children'], 'key'))->toBe(['empresas', 'usuarios'])
        ->and(array_column($porKey['grupo-seguranca']['children'], 'key'))->toBe(['acesso', 'menus', 'configuracoes'])
        ->and($porKey['grupo-cadastros']['label'])->toBe('Cadastros')
        ->and($porKey['grupo-seguranca']['icon'])->toBe('tabler--shield-lock');

    // Principal intocada.
    $principal = collect($secoes)->firstWhere('key', 'principal');
    expect(array_column($principal['items'], 'key'))->toBe(['dashboard']);
});

it('é idempotente e vira no-op quando já existe qualquer grupo', function () {
    app(AplicarMenuPadraoAction::class)->execute();
    $total = MenuPersonalizacao::query()->count();

    // Segunda aplicação não duplica nem altera nada.
    expect(app(AplicarMenuPadraoAction::class)->execute())->toBeFalse()
        ->and(MenuPersonalizacao::query()->count())->toBe($total);

    // Instalação com grupo próprio do cliente também é no-op.
    MenuPersonalizacao::query()->delete();
    MenuPersonalizacao::create([
        'tipo' => 'grupo', 'key' => 'grupo-do-cliente', 'label' => 'Meu Grupo',
        'secao_key' => 'principal', 'e_custom' => true,
    ]);

    expect(app(AplicarMenuPadraoAction::class)->execute())->toBeFalse()
        ->and(MenuPersonalizacao::query()->where('key', 'grupo-cadastros')->exists())->toBeFalse();
});

it('não sobrescreve personalização de item pré-existente', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'empresas', 'label' => 'Organizações', 'ordem' => 9]);

    app(AplicarMenuPadraoAction::class)->execute();

    $empresas = MenuPersonalizacao::query()
        ->where('tipo', TipoPersonalizacaoMenu::Item)
        ->where('key', 'empresas')
        ->firstOrFail();

    // firstOrCreate preservou a linha do cliente (sem grupo_key do padrão).
    expect($empresas->label)->toBe('Organizações')
        ->and($empresas->ordem)->toBe(9)
        ->and($empresas->grupo_key)->toBeNull();
});

it('aplica o menu padrão ao concluir o Setup Wizard', function () {
    $this->seed(RolePermissionSeeder::class);
    marcarInstalado(false);

    Livewire\Livewire::test(App\Livewire\Admin\Setup\SetupWizard::class)
        ->set('nome_cliente', 'Cliente Acme')
        ->call('proximo')
        ->set('nome_sistema', 'ERP Acme')
        ->call('proximo')
        ->set('admin_nome', 'Dono Acme')
        ->set('admin_email', 'dono@acme.com')
        ->set('admin_senha', 'SenhaForte1')
        ->call('concluir');

    expect(MenuPersonalizacao::query()->where('key', 'grupo-cadastros')->exists())->toBeTrue()
        ->and(MenuPersonalizacao::query()->where('key', 'grupo-seguranca')->exists())->toBeTrue();
});
