<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('expõe aria-label "Copiar" quando não há rótulo de texto', function (): void {
    $html = Blade::render('<x-shared.copy-button value="ABC-123" />');

    // Sem rótulo de texto, o nome acessível do botão vem do aria-label.
    expect($html)
        ->toContain('aria-label="Copiar"')
        ->toContain('data-af-copy')
        ->toContain('data-copy-icon');
});

it('renderiza o rótulo de texto quando informado', function (): void {
    $html = Blade::render('<x-shared.copy-button value="ABC-123" label="Copiar código" />');

    // Com rótulo, o texto do span comunica a ação; o aria-label não é necessário.
    expect($html)
        ->toContain('Copiar código')
        ->toContain('data-copy-label')
        ->not->toContain('aria-label="Copiar"');
});

it('marca o ícone decorativo como aria-hidden (a11y)', function (): void {
    $html = Blade::render('<x-shared.copy-button value="ABC-123" />');

    // O ícone (tabler--copy) é puramente decorativo: a ação já é comunicada
    // pelo aria-label/rótulo e pelo toast de sucesso. Esconder do leitor de
    // tela evita ruído redundante. Cobertura total: todo <i class="iconify ...">
    // precisa de aria-hidden.
    expect($html)->toContain('aria-hidden="true"');

    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
