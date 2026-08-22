<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza a variant e o conteúdo do slot', function (): void {
    $html = Blade::render('<x-shared.badge variant="success">Ativo</x-shared.badge>');

    // O badge comunica o significado pelo texto do slot.
    expect($html)
        ->toContain('Ativo')
        ->toContain('badge')
        ->toContain('text-success');
});

it('marca o ícone decorativo como aria-hidden (a11y)', function (): void {
    $html = Blade::render('<x-shared.badge variant="success" icon="tabler--circle-check">Ativo</x-shared.badge>');

    // O ícone do badge é puramente decorativo: o texto do slot ("Ativo") já
    // comunica o significado. Esconder do leitor de tela evita ruído redundante.
    // Cobertura total: todo <i class="iconify ..."> precisa de aria-hidden.
    expect($html)->toContain('aria-hidden="true"');

    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
