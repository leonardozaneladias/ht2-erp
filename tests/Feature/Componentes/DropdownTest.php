<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o trigger default com texto e atributos ARIA', function (): void {
    $html = Blade::render('<x-shared.dropdown><x-shared.dropdown-item>Editar</x-shared.dropdown-item></x-shared.dropdown>');

    // O trigger default é nomeado pelo texto "Ações" e expõe o contrato de menu.
    expect($html)
        ->toContain('Ações')
        ->toContain('aria-expanded="false"')
        ->toContain('aria-haspopup="menu"')
        ->toContain('role="menu"');
});

it('marca o chevron decorativo do trigger default como aria-hidden (a11y)', function (): void {
    $html = Blade::render('<x-shared.dropdown><x-shared.dropdown-item>Editar</x-shared.dropdown-item></x-shared.dropdown>');

    // O chevron-down do trigger default é puramente decorativo: o botão já é
    // nomeado por "Ações" e o estado aberto/fechado vem de aria-expanded.
    // Esconder do leitor de tela evita ruído redundante.
    // Cobertura total: todo <i class="iconify ..."> precisa de aria-hidden.
    expect($html)->toContain('aria-hidden="true"');

    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
