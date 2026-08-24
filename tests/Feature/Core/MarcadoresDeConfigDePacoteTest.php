<?php

declare(strict_types=1);

/*
 * O gerador injeta permissões e itens de menu no config do pacote procurando
 * por comentários-marcador. Sem eles, injetarConfigPacote() pula os dois blocos
 * — e até 2026-08-24 ainda reportava "criado (permissões + menu)".
 *
 * Consequência medida: 2 dos 3 pacotes (exemplo-demo e fiscal-br) tinham perdido
 * os marcadores. Gerar um recurso ali produzia 19 arquivos, uma rota e uma tela
 * INALCANÇÁVEL: sem item de menu, e com o gate negando porque a permissão nunca
 * chegou ao catálogo. Nada ficava vermelho.
 *
 * Um pacote novo nasce certo (o stub tem os marcadores); este teste existe para
 * o caso de alguém reescrever um config e removê-los sem perceber.
 */

/** @return list<array{0: string, 1: string}> [slug do pacote, caminho do config] */
function configsDePacote(): array
{
    $raiz = dirname(__DIR__, 3);

    return collect(glob($raiz . '/packages/extensao-*/config/*.php') ?: [])
        ->map(fn (string $caminho): array => [
            basename(dirname($caminho, 2)),
            $caminho,
        ])
        ->values()
        ->all();
}

it('encontra os configs de pacote', function (): void {
    expect(configsDePacote())->not->toBeEmpty();
});

it('o config do pacote tem os marcadores que o gerador procura', function (string $pacote, string $caminho): void {
    $conteudo = (string) file_get_contents($caminho);

    expect($conteudo)->toContain(
        '// make:modulo insere as permissões do módulo acima desta linha',
    );

    expect($conteudo)->toContain(
        '// make:modulo insere os itens de menu do módulo acima desta linha',
    );
})->with(fn () => configsDePacote());

it('o stub de config entrega os dois marcadores a um pacote novo', function (): void {
    $stub = (string) file_get_contents(
        dirname(__DIR__, 3) . '/packages/core/stubs/extensao/config.stub',
    );

    expect($stub)->toContain('// make:modulo insere as permissões do módulo acima desta linha')
        ->and($stub)->toContain('// make:modulo insere os itens de menu do módulo acima desta linha');
});
