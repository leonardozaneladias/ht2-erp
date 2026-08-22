<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('dá nome acessível ao container role="menu" (aria-label)', function (): void {
    $html = Blade::render('<x-admin.row-actions><span>Editar</span></x-admin.row-actions>');

    // O botão gatilho já carrega aria-label="Ações"; pela WAI-ARIA Menu Button
    // Pattern, o container role="menu" também precisa de nome acessível, senão o
    // leitor de tela anuncia apenas "menu" sem rótulo ao abri-lo. Esperamos a
    // mesma label nos dois elementos (gatilho + menu) -> 2 ocorrências.
    expect(substr_count($html, 'aria-label="Ações"'))->toBe(2);
});

it('propaga a label customizada ao gatilho e ao menu', function (): void {
    $html = Blade::render('<x-admin.row-actions label="Opções do registro"><span>Editar</span></x-admin.row-actions>');

    expect(substr_count($html, 'aria-label="Opções do registro"'))->toBe(2);
});

it('mantém a semântica de menu e do gatilho', function (): void {
    $html = Blade::render('<x-admin.row-actions><span>Editar</span></x-admin.row-actions>');

    expect($html)
        ->toContain('role="menu"')
        ->toContain('aria-orientation="vertical"')
        ->toContain('aria-haspopup="menu"')
        ->toContain('Editar');
});

it('não renderiza nada quando o slot está vazio', function (): void {
    $html = Blade::render('<x-admin.row-actions></x-admin.row-actions>');

    expect(trim($html))->toBe('');
});

it('marca o ícone kebab como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-admin.row-actions><span>Editar</span></x-admin.row-actions>');

    // O nome do gatilho vem do aria-label ("Ações"); o ícone "⋮" é só visual e
    // não deve ser anunciado pelo leitor de tela.
    expect($html)->toMatch('/<i class="iconify tabler--dots-vertical[^>]*aria-hidden="true"/');
});
