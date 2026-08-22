<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('no modo Preline (default) associa a aba ao painel via aria-controls', function (): void {
    $html = Blade::render('<x-shared.tab-trigger id="dados">Dados</x-shared.tab-trigger>');

    // WAI-ARIA Tabs Pattern: o role="tab" controla o role="tabpanel" via aria-controls.
    // No modo Preline o painel "panel-{id}" sempre existe no DOM (apenas hidden).
    expect($html)->toContain('role="tab"')
        ->and($html)->toContain('id="tab-dados"')
        ->and($html)->toContain('aria-controls="panel-dados"')
        ->and($html)->toContain('data-hs-tab="#panel-dados"');
});

it('no modo server-driven omite aria-controls por padrão (não há painel no DOM)', function (): void {
    // painel-perfil/painel-pessoa trocam o conteúdo por @if no servidor, sem
    // x-shared.tab-panel: emitir aria-controls criaria referência pendente.
    $html = Blade::render('<x-shared.tab-trigger id="permissoes" :preline="false" wire:click="$set(\'aba\', \'permissoes\')">Permissões</x-shared.tab-trigger>');

    expect($html)->toContain('role="tab"')
        ->and($html)->not->toContain('aria-controls')
        ->and($html)->not->toContain('data-hs-tab');
});

it('no modo server-driven com controls associa a aba ao tab-panel correspondente', function (): void {
    // form-funcionario (RH): server-driven (wire:click) MAS usa x-shared.tab-panel,
    // então o painel "panel-{id}" existe no DOM e a associação é válida.
    $html = Blade::render('<x-shared.tab-trigger id="identificacao" :preline="false" :controls="true" :active="true">Identificação</x-shared.tab-trigger>');

    expect($html)->toContain('aria-controls="panel-identificacao"')
        // server-driven não usa o JS do Preline para trocar de aba.
        ->and($html)->not->toContain('data-hs-tab');
});

it('aceita um id de painel customizado em controls', function (): void {
    $html = Blade::render('<x-shared.tab-trigger id="aba-x" :preline="false" controls="painel-custom">Aba X</x-shared.tab-trigger>');

    expect($html)->toContain('aria-controls="painel-custom"');
});

it('controls=false força a omissão do aria-controls mesmo no modo Preline', function (): void {
    $html = Blade::render('<x-shared.tab-trigger id="dados" :controls="false">Dados</x-shared.tab-trigger>');

    expect($html)->not->toContain('aria-controls');
});

it('mantém role, estado e indicador de erro (regressão)', function (): void {
    $html = Blade::render('<x-shared.tab-trigger id="contato" :preline="false" :active="true" :has-error="true" icon="tabler--user">Contato</x-shared.tab-trigger>');

    expect($html)->toContain('role="tab"')
        ->and($html)->toContain('aria-selected="true"')
        ->and($html)->toContain('iconify')
        ->and($html)->toContain('(há erros nesta aba)');
});

it('marca o ícone do gatilho como decorativo (aria-hidden) — o rótulo da aba já nomeia o controle', function (): void {
    // O ícone é reforço visual: o leitor de tela anuncia a aba pelo texto do <span>{{ $slot }}</span>.
    // Cobertura total: todo <i class="iconify ..."> precisa de aria-hidden="true".
    $html = Blade::render('<x-shared.tab-trigger id="dados" icon="tabler--user">Dados</x-shared.tab-trigger>');

    preg_match_all('/<i\b[^>]*class="[^"]*iconify[^"]*"[^>]*>/s', $html, $icones);

    expect($icones[0])->not->toBeEmpty();

    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
