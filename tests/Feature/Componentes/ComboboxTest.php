<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o select-search single sobre o combobox (gatilho + select nativo oculto)', function (): void {
    $html = Blade::render(
        '<x-shared.select-search name="uf" label="UF" :options="[\'SP\' => \'São Paulo\', \'RJ\' => \'Rio de Janeiro\']" value="RJ" />',
    );

    // Camada visual = combobox (gatilho compacto).
    expect($html)
        ->toContain('x-data="comboBox(')
        ->toContain('data-testid="combobox-trigger"')
        // Fonte de verdade = <select> nativo oculto, mas presente (POST/wire/old).
        ->toContain('x-ref="native"')
        ->toContain('sr-only')
        ->toContain('São Paulo')
        ->toContain('Rio de Janeiro');

    // O value inicial marca a opcao certa no <select>.
    expect($html)->toMatch('/<option\s+value="RJ"\s+selected/');
});

it('renderiza o select-search multiple com name[] e selecao inicial', function (): void {
    $html = Blade::render(
        '<x-shared.select-search name="filtros" :options="[\'a\' => \'Alfa\', \'b\' => \'Beta\', \'c\' => \'Gama\']" :value="[\'a\', \'c\']" multiple />',
    );

    expect($html)
        ->toContain('name="filtros[]"')
        ->toContain('multiple')
        ->toContain('Alfa')
        ->toContain('Gama');

    // 'a' e 'c' marcados; 'b' nao.
    expect($html)
        ->toMatch('/<option\s+value="a"\s+selected/')
        ->toMatch('/<option\s+value="c"\s+selected/')
        ->not->toMatch('/<option\s+value="b"\s+selected/');
});

it('renderiza grupos (optgroup) no select-search', function (): void {
    $html = Blade::render(
        '<x-shared.select-search name="cat" :options="[\'Operacional\' => [[\'value\' => \'sup\', \'label\' => \'Suporte\']], \'Negocios\' => [[\'value\' => \'com\', \'label\' => \'Comercial\']]]" />',
    );

    expect($html)
        ->toContain('<optgroup label="Operacional"')
        ->toContain('<optgroup label="Negocios"')
        ->toContain('Suporte')
        ->toContain('Comercial');
});

it('associa o label ao gatilho real do combobox (a11y do select-search)', function (): void {
    $html = Blade::render(
        '<x-shared.select-search name="uf" label="UF" :options="[\'SP\' => \'São Paulo\']" />',
    );

    // O <label> aponta para o gatilho (controle real e clicavel), nao para o
    // <select> oculto/aria-hidden; e expoe um id para ser referenciado.
    expect($html)
        ->toContain('id="uf-label"')
        ->toContain('for="uf-trigger"');

    // O gatilho carrega o id e e nomeado por label + valor (span do resumo),
    // sem poluir o nome com o botao "Limpar selecao" aninhado.
    expect($html)
        ->toContain('id="uf-trigger"')
        ->toContain('aria-labelledby="uf-label uf-value"')
        ->toContain('id="uf-value"');
});

it('liga o hint ao gatilho do combobox via aria-describedby', function (): void {
    $html = Blade::render(
        '<x-shared.select-search name="uf" label="UF" hint="Selecione o estado" :options="[\'SP\' => \'São Paulo\']" />',
    );

    expect($html)
        ->toContain('aria-describedby="uf-hint"')
        ->toContain('id="uf-hint"');
});

it('nomeia o gatilho do filtro multi-select (pg-filter) via rotulo sr-only interno + valor', function (): void {
    $html = Blade::render(
        '<x-shared.combobox mode="pg-filter" field="log_name" table-name="auditoria-table" label="Log" :options="$options" />',
        ['options' => [['value' => 'a', 'label' => 'A']]],
    );

    // Sem <label> externo (labelId), o combobox gera um rotulo interno sr-only com
    // o texto do `label` e o referencia no gatilho junto do valor (resumo), dando
    // nome acessivel ao filtro inline que nao tem <label> visivel (WCAG 4.1.2).
    expect($html)
        ->toContain('id="combobox-auditoria-table-log_name-label" class="sr-only">Log<')
        ->toContain('aria-labelledby="combobox-auditoria-table-log_name-label combobox-auditoria-table-log_name-value"')
        ->toContain('id="combobox-auditoria-table-log_name-value"')
        ->toContain('data-testid="combobox-trigger"');
});

it('nao nomeia o gatilho pg-filter sem label (sem referencia pendente)', function (): void {
    $html = Blade::render(
        '<x-shared.combobox mode="pg-filter" field="log_name" table-name="auditoria-table" :options="$options" />',
        ['options' => [['value' => 'a', 'label' => 'A']]],
    );

    // Sem label nem labelId: gatilho sem nome (legado), e nenhum rotulo interno
    // gerado -> nada de aria-labelledby apontando para id inexistente.
    expect($html)
        ->not->toContain('aria-labelledby=')
        ->not->toContain('class="sr-only">');
});

it('nao nomeia o gatilho em modo form sem labelId nem label (legado)', function (): void {
    // Contrato base do combobox: sem labelId nem label, o gatilho segue sem nome e
    // sem referencia pendente (comportamento legado). Os filtros boolean/select do
    // PowerGrid ja passam `label` (associacao label<->controle); o filtro de operador
    // do input-text ainda usa mode="form" sem label e depende deste comportamento.
    $html = Blade::render('<x-shared.combobox mode="form" id="filtro-x" />');

    expect($html)
        ->not->toContain('aria-labelledby=')
        ->toContain('id="filtro-x-trigger"');
});

it('marca estado de erro no gatilho do combobox', function (): void {
    $html = Blade::render(
        '<x-shared.combobox mode="form" :invalid="true" />',
    );

    expect($html)->toContain('border-danger');
});

it('renderiza o combobox em modo pg-filter com os data-attributes do PowerGrid', function (): void {
    $html = Blade::render(
        '<x-shared.combobox mode="pg-filter" field="log_name" table-name="auditoria-table" label="Log" :options="$options" />',
        ['options' => [['value' => 'canalalpha', 'label' => 'canalalpha']]],
    );

    expect($html)
        ->toContain('data-testid="combobox-trigger"')
        ->toContain('data-combobox-field="log_name"')
        ->toContain('data-combobox-table="auditoria-table"')
        // Opcoes vao para o factory via JSON (config), nao como <option> nativas.
        ->toContain('x-data="comboBox(')
        ->toContain('pg-filter');
});

it('marca os ícones decorativos do combobox como aria-hidden (PEND-20)', function (): void {
    // Modo single + searchable cobre 4 dos 5 ícones: chevron do gatilho, "x" do
    // botão limpar, lupa da busca e o check da opção selecionada (branch single).
    // Todos sao puramente decorativos: cada controle ja tem nome acessivel
    // (aria-label/aria-labelledby) e o estado de selecao e anunciado por
    // aria-selected no role="option" — o leitor de tela nao deve anuncia-los.
    $single = Blade::render(
        '<x-shared.combobox mode="pg-filter" field="status" table-name="t" label="Status" :options="$options" />',
        ['options' => [['value' => 'a', 'label' => 'A']]],
    );

    expect($single)
        ->toMatch('/iconify tabler--chevron-down[^"]*"\s+aria-hidden="true"/s')
        ->toMatch('/iconify tabler--x[^"]*"\s+aria-hidden="true"/s')
        ->toMatch('/iconify tabler--search[^"]*"\s+aria-hidden="true"/s')
        ->toMatch('/iconify tabler--check text-primary[^"]*"\s+aria-hidden="true"/s');

    // Modo multiple expoe o 5o ícone: o check dentro do checkbox visual.
    $multiple = Blade::render(
        '<x-shared.combobox mode="pg-filter" field="status" table-name="t" label="Status" multiple :options="$options" />',
        ['options' => [['value' => 'a', 'label' => 'A']]],
    );

    expect($multiple)->toMatch('/iconify tabler--check text-\[0\.7rem\]"\s+aria-hidden="true"/s');
});
