<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('oculta o divisor de ícone dos leitores de tela (aria-hidden)', function (): void {
    $html = Blade::render(
        '<x-shared.breadcrumb :items="$items" />',
        ['items' => [
            ['label' => 'Admin', 'url' => '/admin'],
            ['label' => 'Clientes', 'current' => true],
        ]],
    );

    // O chevron separador é puramente decorativo: leitores de tela não devem
    // anunciá-lo. O divisor de ícone (default) deve ser ocultado igual ao de
    // texto, que já carrega aria-hidden="true".
    expect($html)->toContain('aria-hidden="true"');
});

it('oculta também o divisor de texto customizado (regressão)', function (): void {
    $html = Blade::render(
        '<x-shared.breadcrumb :items="$items" divider="/" />',
        ['items' => [
            ['label' => 'Admin', 'url' => '/admin'],
            ['label' => 'Clientes', 'current' => true],
        ]],
    );

    // O separador textual continua decorativo e oculto ao leitor de tela.
    expect($html)->toContain('aria-hidden="true">/</span>');
});

it('oculta os ícones decorativos dos itens (com e sem link) dos leitores de tela', function (): void {
    $html = Blade::render(
        '<x-shared.breadcrumb :items="$items" />',
        ['items' => [
            // Item com link + ícone (ramo do <a>).
            ['label' => 'Início', 'url' => '/admin', 'icon' => 'tabler--home'],
            // Item atual + ícone (ramo do <span>, sem link).
            ['label' => 'Clientes', 'icon' => 'tabler--users', 'current' => true],
        ]],
    );

    // O label textual de cada item já é anunciado; o ícone só reforça
    // visualmente. Todo <i class="iconify ..."> precisa ser decorativo
    // (aria-hidden="true") para não gerar ruído no leitor de tela —
    // cobertura total: nenhum ícone iconify pode restar exposto.
    preg_match_all('/<i\s+class="iconify[^"]*"(?<attrs>[^>]*)>/', $html, $matches);

    expect($matches['attrs'])->not->toBeEmpty();

    foreach ($matches['attrs'] as $attrs) {
        expect($attrs)->toContain('aria-hidden="true"');
    }
});

it('mantém a semântica de navegação do breadcrumb', function (): void {
    $html = Blade::render(
        '<x-shared.breadcrumb :items="$items" />',
        ['items' => [
            ['label' => 'Admin', 'url' => '/admin'],
            ['label' => 'Clientes', 'current' => true],
        ]],
    );

    // Regressão: o landmark de navegação e o item atual continuam marcados.
    expect($html)
        ->toContain('aria-label="Breadcrumb"')
        ->toContain('aria-current="page"');
});
