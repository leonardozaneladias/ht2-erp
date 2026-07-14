<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — o ícone do item de menu é puramente decorativo: o nome
// acessível do item vem SEMPRE do texto (slot), nunca do ícone. Logo todo ícone
// recebe aria-hidden="true" (mesma convenção de button/unsaved-bar/breadcrumb).
// Item de menu é a fundação de todos os menus de ação de linha (_acoes) do ERP.

it('marca o ícone como decorativo (aria-hidden) no item-link', function (): void {
    $html = Blade::render('<x-shared.dropdown-item href="/admin/x" icon="tabler--edit">Editar</x-shared.dropdown-item>');

    expect($html)
        ->toMatch('/<i class="iconify tabler--edit[^>]*aria-hidden="true"/')
        ->toContain('<a')
        ->toContain('href="/admin/x"')
        ->toContain('role="menuitem"')
        ->toContain('Editar');
});

it('marca o ícone como decorativo (aria-hidden) no item-botão', function (): void {
    $html = Blade::render('<x-shared.dropdown-item icon="tabler--trash">Excluir</x-shared.dropdown-item>');

    expect($html)
        ->toMatch('/<i class="iconify tabler--trash[^>]*aria-hidden="true"/')
        ->toContain('<button')
        ->toContain('role="menuitem"')
        ->toContain('Excluir');
});

it('marca o ícone como decorativo (aria-hidden) no item desabilitado com href', function (): void {
    $html = Blade::render('<x-shared.dropdown-item href="/admin/x" icon="tabler--lock" :disabled="true">Bloqueado</x-shared.dropdown-item>');

    // href + disabled => <span aria-disabled> (não navegável), ícone ainda decorativo.
    expect($html)
        ->toMatch('/<i class="iconify tabler--lock[^>]*aria-hidden="true"/')
        ->toContain('aria-disabled="true"')
        ->toContain('role="menuitem"');
});

it('sem ícone não renderiza <i> e o nome vem do slot (regressão)', function (): void {
    $html = Blade::render('<x-shared.dropdown-item>Apenas texto</x-shared.dropdown-item>');

    expect($html)
        ->not->toContain('<i class="iconify')
        ->toContain('Apenas texto');
});

it('aplica a classe da variante (regressão)', function (): void {
    $html = Blade::render('<x-shared.dropdown-item variant="danger">Excluir</x-shared.dropdown-item>');

    expect($html)->toContain('text-danger');
});
