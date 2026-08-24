<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Menus\GestaoMenus;
use HT2ML\Core\Models\MenuPersonalizacao;
use HT2ML\Core\Services\Admin\Menu\MenuService;
use HT2ML\Core\Support\Menu\IconesMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Contrato de a11y dos ícones `iconify` da tela de Gestão de Menus (PEND-21).
// A tela controla o menu lateral de todo o ERP e renderiza as partials
// linha-item, preview-sidebar e grade-icones. Os ícones decorativos (drag
// handles, chevrons, glifo ao lado do rótulo) recebem aria-hidden; os
// indicadores de status sem texto visível (personalizado/restrito/público)
// ganham um <span class="sr-only">; o seletor de ícone (botão icon-only) ganha
// nome acessível e estado.

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    app(MenuService::class)->invalidarCache();
});

/**
 * Extrai as tags <i> com classe iconify (multilinha) que NÃO declaram
 * aria-hidden. Regex por tag completa (não por linha), o método recomendado
 * pela varredura da PEND-21 — evita falsos-positivos de <i> multilinha.
 *
 * @return list<string>
 */
$iconesSemAriaHidden = function (string $html): array {
    preg_match_all('/<i\b[^>]*>/s', $html, $tags);

    return array_values(array_filter(
        $tags[0],
        fn (string $tag): bool => str_contains($tag, 'iconify') && ! str_contains($tag, 'aria-hidden'),
    ));
};

it('marca todo ícone iconify da tela de menus como aria-hidden (decorativo)', function () use ($iconesSemAriaHidden) {
    // Força um item personalizado para que o indicador "pencil-star" apareça.
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe', 'ordem' => 1]);
    app(MenuService::class)->invalidarCache();

    $html = Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->html();

    expect($iconesSemAriaHidden($html))->toBe([]);
});

it('expõe os indicadores de status do item ao leitor de tela via sr-only', function () {
    MenuPersonalizacao::create(['tipo' => 'item', 'key' => 'usuarios', 'label' => 'Equipe', 'ordem' => 1]);
    app(MenuService::class)->invalidarCache();

    $html = Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->html();

    // O glifo é decorativo (aria-hidden); o significado vai num <span class="sr-only">.
    expect($html)
        ->toMatch('/<span class="sr-only">\s*Personalizado \(difere do padrão do módulo\)\s*<\/span>/')
        ->and($html)->toContain('class="sr-only">Visível para');
});

it('dá nome acessível e estado ao seletor de ícone (grade-icones)', function () {
    $primeiro = IconesMenu::disponiveis()[0];

    $html = Livewire::actingAs($this->admin, 'admin')
        ->test(GestaoMenus::class)
        ->html();

    // Botão icon-only: o nome acessível identifica a ação; aria-pressed comunica
    // qual ícone está selecionado; o glifo interno é decorativo.
    expect($html)
        ->toContain('aria-label="Selecionar ícone ' . $primeiro . '"')
        ->toContain('aria-pressed=');
});
