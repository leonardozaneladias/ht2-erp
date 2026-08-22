<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza a mensagem de vazio quando não há programações', function (): void {
    $html = Blade::render('<x-admin.timeline-table />');

    expect($html)->toContain('Nenhuma programação cadastrada.');
});

it('renderiza a linha com status e período da programação', function (): void {
    $programacoes = [
        [
            'periodo' => '01/01/2026 - 31/01/2026',
            'valor_formatado' => 'R$ 1.000,00',
            'status' => 'ativa',
        ],
    ];

    $html = Blade::render(
        '<x-admin.timeline-table :programacoes="$programacoes" />',
        ['programacoes' => $programacoes],
    );

    expect($html)
        ->toContain('ATIVA')
        ->toContain('01/01/2026 - 31/01/2026')
        ->toContain('R$ 1.000,00');
});

it('marca os ícones decorativos das ações como aria-hidden (a11y)', function (): void {
    $programacoes = [
        [
            'periodo' => '01/01/2026 - 31/01/2026',
            'status' => 'ativa',
            'actions' => [
                ['label' => 'Editar', 'icon' => 'tabler--pencil', 'href' => '#'],
                ['label' => 'Remover', 'icon' => 'tabler--trash', 'type' => 'button'],
            ],
        ],
    ];

    $html = Blade::render(
        '<x-admin.timeline-table :programacoes="$programacoes" />',
        ['programacoes' => $programacoes],
    );

    // Cada ícone de ação acompanha o <span> com o rótulo ("Editar"/"Remover"),
    // que comunica o significado; o ícone é puramente decorativo e deve ser
    // escondido do leitor de tela. Cobertura total: todo <i class="iconify ...">
    // precisa de aria-hidden.
    preg_match_all('/<i\b[^>]*\bclass="[^"]*\biconify\b[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();
    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
