<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o painel de pré-visualização com a paleta', function (): void {
    $html = Blade::render(
        '<x-admin.appearance-preview-panel logo-light="/l.png" logo-dark="/d.png" logo-sm="/s.png" />',
    );

    expect($html)
        ->toContain('Pré-visualização')
        ->toContain('Paleta')
        ->toContain('menu recolhido');
});

it('marca o ícone decorativo como aria-hidden (a11y)', function (): void {
    $html = Blade::render(
        '<x-admin.appearance-preview-panel logo-light="/l.png" logo-dark="/d.png" logo-sm="/s.png" />',
    );

    // O ícone tabler--layout-sidebar acompanha o texto "menu recolhido", que
    // comunica o significado; o ícone é puramente decorativo e deve ser
    // escondido do leitor de tela. Cobertura total: todo <i class="iconify ...">
    // precisa de aria-hidden.
    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
