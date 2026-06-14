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
