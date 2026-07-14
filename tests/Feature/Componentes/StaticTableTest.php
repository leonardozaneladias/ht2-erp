<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('associa as células ao cabeçalho com scope="col" em cada coluna do modo :headers', function (): void {
    $html = Blade::render(
        '<x-shared.static-table :headers="[\'Nome\', \'E-mail\']"><tr><td>Ana</td><td>ana@x.test</td></tr></x-shared.static-table>',
    );

    // scope="col" liga as células de dados ao nome da coluna (WCAG 1.3.1).
    // Cada cabeçalho gerado pelo array precisa carregá-lo.
    expect(substr_count($html, 'scope="col"'))->toBe(2);
    expect($html)
        ->toContain('<th scope="col">Nome</th>')
        ->toContain('<th scope="col">E-mail</th>');
});

it('mostra a mensagem de vazio com colspan correto quando não há linhas', function (): void {
    $html = Blade::render(
        '<x-shared.static-table :headers="[\'Nome\', \'E-mail\']" empty-message="Nada aqui." />',
    );

    // Regressão do estado vazio: colspan cobre todas as colunas e a mensagem aparece.
    expect($html)
        ->toContain('colspan="2"')
        ->toContain('Nada aqui.');
});

it('no modo slot head o colspan do vazio conta os <th> do markup', function (): void {
    $html = Blade::render(
        <<<'BLADE'
        <x-shared.static-table empty-message="Sem linhas.">
            <x-slot:head><tr><th scope="col">A</th><th scope="col">B</th><th scope="col" class="text-right">C</th></tr></x-slot:head>
        </x-shared.static-table>
        BLADE,
    );

    // Regressão da migração das listagens: com head via slot o colspan saía 1 e a
    // mensagem de vazio ficava ancorada na primeira coluna em vez de atravessar a tabela.
    expect($html)
        ->toContain('colspan="3"')
        ->toContain('Sem linhas.');
});

it('não injeta scope quando o cabeçalho vem pelo slot head (controle do consumidor)', function (): void {
    $html = Blade::render(
        '<x-shared.static-table><x-slot:head><tr><th>Custom</th></tr></x-slot:head><tr><td>x</td></tr></x-shared.static-table>',
    );

    // No modo slot o consumidor escreve os <th> à mão; o componente não os altera.
    expect($html)
        ->toContain('<th>Custom</th>')
        ->not->toContain('scope="col"');
});
