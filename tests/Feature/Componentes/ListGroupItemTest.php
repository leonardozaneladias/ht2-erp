<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('anuncia a página atual com aria-current="page" no item de navegação ativo', function (): void {
    $html = Blade::render(
        '<x-shared.list-group-item href="/admin/conta" active>Conta</x-shared.list-group-item>',
    );

    // Item de navegação ativo informa a leitores de tela qual item corresponde à
    // página atual (WAI-ARIA aria-current), como já fazem a sidebar e o breadcrumb.
    expect($html)
        ->toContain('href="/admin/conta"')
        ->toContain('aria-current="page"');
});

it('marca o item estático ativo (sem href) com aria-current="true"', function (): void {
    $html = Blade::render(
        '<x-shared.list-group-item active>Selecionado</x-shared.list-group-item>',
    );

    // Sem href o item não navega; "true" indica o item atual do conjunto sem
    // implicar navegação de página.
    expect($html)->toContain('aria-current="true"');
});

it('não emite aria-current em item inativo (navegação ou estático)', function (): void {
    $navegacao = Blade::render(
        '<x-shared.list-group-item href="/admin/conta">Conta</x-shared.list-group-item>',
    );
    $estatico = Blade::render(
        '<x-shared.list-group-item>Item</x-shared.list-group-item>',
    );

    // Regressão: aria-current só aparece quando o item está ativo.
    expect($navegacao)->not->toContain('aria-current');
    expect($estatico)->not->toContain('aria-current');
});

it('respeita um aria-current explícito do consumidor (override compatível)', function (): void {
    $html = Blade::render(
        '<x-shared.list-group-item href="#step-2" active aria-current="step">Passo 2</x-shared.list-group-item>',
    );

    // merge mantém o default sobrescrevível: o valor passado pelo consumidor vence
    // e não há duplicação do atributo.
    expect($html)
        ->toContain('aria-current="step"')
        ->not->toContain('aria-current="page"');
    expect(substr_count($html, 'aria-current'))->toBe(1);
});
