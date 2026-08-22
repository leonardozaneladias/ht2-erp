<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/*
 * x-shared.section-nav — navegação lateral de seções.
 *
 * Extraído da FichaFuncionario para o core, porque o formulário de funcionário precisa do
 * mesmo padrão: com muitas seções, a barra de abas horizontal quebra em duas linhas e vira
 * um "muro" que ninguém escaneia.
 *
 * Contratos afirmados aqui: a seção ativa é a única com aria-current="page" (e as demais
 * OMITEM o atributo, em vez de valer "false"); o agrupamento; e o indicador de erro, que é
 * o que o formulário precisa e a ficha não tinha.
 */

/** @return array<string, array<string, mixed>> */
function secoesDeExemplo(): array
{
    return [
        'identificacao' => ['rotulo' => 'Identificação', 'icone' => 'tabler--user', 'grupo' => 'Cadastro'],
        'documentos' => ['rotulo' => 'Documentos', 'icone' => 'tabler--id', 'grupo' => 'Cadastro', 'erro' => true],
        'ferias' => ['rotulo' => 'Férias', 'icone' => 'tabler--beach', 'grupo' => 'Gestão'],
    ];
}

it('marca só a seção ativa com aria-current e omite o atributo nas demais', function (): void {
    $html = Blade::render(
        '<x-shared.section-nav :sections="$secoes" active="documentos" />',
        ['secoes' => secoesDeExemplo()],
    );

    // aria-current="false" é inválido: o atributo deve ser omitido quando não é a atual.
    expect($html)
        ->toContain('aria-current="page"')
        ->not->toContain('aria-current="false"')
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1);
});

it('renderiza os cabeçalhos de grupo na ordem das seções', function (): void {
    $html = Blade::render(
        '<x-shared.section-nav :sections="$secoes" active="identificacao" />',
        ['secoes' => secoesDeExemplo()],
    );

    expect($html)
        ->toContain('Cadastro')
        ->toContain('Gestão')
        ->and(strpos($html, 'Cadastro'))->toBeLessThan(strpos($html, 'Gestão'));
});

it('sinaliza a seção com erro de validação', function (): void {
    $html = Blade::render(
        '<x-shared.section-nav :sections="$secoes" active="identificacao" />',
        ['secoes' => secoesDeExemplo()],
    );

    // Ponto vermelho + texto para leitor de tela (mesma convenção do x-shared.tab-trigger).
    expect($html)
        ->toContain('bg-danger')
        ->toContain('(há erros nesta seção)')
        ->and(substr_count($html, '(há erros nesta seção)'))->toBe(1);
});

it('dispara a ação Livewire com o id da seção', function (): void {
    $html = Blade::render(
        '<x-shared.section-nav :sections="$secoes" active="identificacao" action="irPara" />',
        ['secoes' => secoesDeExemplo()],
    );

    expect($html)
        ->toContain('wire:click="irPara(\'documentos\')"')
        ->toContain('wire:key="section-nav-documentos"');
});

it('funciona sem grupos — é como a ficha a usa, e não deve renderizar cabeçalho vazio', function (): void {
    $html = Blade::render(
        '<x-shared.section-nav :sections="$secoes" active="a" />',
        ['secoes' => [
            'a' => ['rotulo' => 'Visão geral', 'icone' => 'tabler--eye'],
            'b' => ['rotulo' => 'Dados', 'icone' => 'tabler--list'],
        ]],
    );

    expect($html)
        ->toContain('Visão geral')
        ->toContain('Dados')
        ->not->toContain('uppercase'); // sem grupo, sem cabeçalho de grupo
});
