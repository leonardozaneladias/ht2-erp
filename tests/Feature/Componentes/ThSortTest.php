<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('expõe a direção de ordenação ascendente via aria-sort quando a coluna está ativa', function (): void {
    $html = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort" dir="asc">Nome</x-admin.table.th-sort>',
        ['sort' => 'nome'],
    );

    // O estado de ordenação precisa ser anunciado a leitores de tela (WCAG 4.1.2),
    // não apenas indicado pela seta visual.
    expect($html)->toContain('aria-sort="ascending"');
});

it('expõe a direção descendente via aria-sort', function (): void {
    $html = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort" dir="desc">Nome</x-admin.table.th-sort>',
        ['sort' => 'nome'],
    );

    expect($html)->toContain('aria-sort="descending"');
});

it('marca aria-sort="none" nas colunas não ordenadas', function (): void {
    $html = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort" dir="asc">Nome</x-admin.table.th-sort>',
        ['sort' => 'email'],
    );

    // Coluna ordenável porém não ativa: anuncia que pode ser ordenada, mas não está.
    expect($html)->toContain('aria-sort="none"');
});

it('associa o cabeçalho à coluna com scope="col" (regressão)', function (): void {
    $html = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort">Nome</x-admin.table.th-sort>',
        ['sort' => null],
    );

    // scope="col" liga as células de dados ao nome da coluna (WCAG 1.3.1) e o
    // gatilho de ordenação por Livewire continua presente.
    expect($html)
        ->toContain('scope="col"')
        ->toContain('aria-sort="none"')
        ->toContain("wire:click=\"ordenarPor('nome')\"");
});

it('marca o ícone de ordenação como aria-hidden em ambos os estados', function (): void {
    // Coluna ativa renderiza a seta de direção (arrow-up/down); coluna inativa
    // renderiza o indicador "ordenável" (arrows-sort). Ambos são puramente visuais:
    // a direção já é anunciada por aria-sort no <th> (casos acima), então a seta não
    // deve ser narrada — caso contrário o leitor de tela duplicaria o estado.
    $ativo = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort" dir="asc">Nome</x-admin.table.th-sort>',
        ['sort' => 'nome'],
    );
    $inativo = Blade::render(
        '<x-admin.table.th-sort column="nome" :sort="$sort">Nome</x-admin.table.th-sort>',
        ['sort' => 'email'],
    );

    foreach ([$ativo, $inativo] as $html) {
        // O <span> do rótulo (slot) não é iconify; só a seta é. Cobertura total:
        // nenhum ícone iconify pode restar sem aria-hidden.
        preg_match_all('/<span\b[^>]*iconify[^>]*>/', $html, $todos);
        preg_match_all('/<span\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);
        expect($todos[0])->toHaveCount(1);
        expect($comHidden[0])->toHaveCount(count($todos[0]));
    }
});
