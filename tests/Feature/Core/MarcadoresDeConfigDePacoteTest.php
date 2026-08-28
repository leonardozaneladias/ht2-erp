<?php

declare(strict_types=1);

use HT2ML\Core\Console\Commands\MakeModuloCommand;

/*
 * O gerador insere o recurso no config do pacote procurando um comentário-
 * marcador. Sem ele, injetarConfigPacote() pula o bloco — e até 2026-08-24
 * ainda reportava "criado (permissões + menu)" mesmo assim.
 *
 * Consequência medida: 2 dos 3 pacotes (exemplo-demo e fiscal-br) tinham perdido
 * os marcadores. Gerar um recurso ali produzia 19 arquivos, uma rota e uma tela
 * INALCANÇÁVEL: sem item de menu, e com o gate negando porque a permissão nunca
 * chegou ao catálogo. Nada ficava vermelho.
 *
 * Eram DOIS marcadores — um para permissões, outro para itens de menu — até o
 * ModuloBuilder passar a derivar os dois da chave do recurso (ADR-0021). Hoje é
 * um só. Quando os dois viraram um, este teste ainda pinava o texto antigo e
 * acusou a divergência: prestou o serviço para o qual foi escrito. Para não
 * depender de tê-lo prestado, o marcador agora é lido da constante do próprio
 * comando — o teste não tem mais como afirmar um marcador que o gerador não usa.
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

it('o config do pacote tem o marcador que o gerador procura', function (string $pacote, string $caminho): void {
    expect((string) file_get_contents($caminho))
        ->toContain(MakeModuloCommand::MARCADOR_RECURSOS);
})->with(fn () => configsDePacote());

it('o stub de config entrega o marcador a um pacote novo', function (): void {
    $stub = (string) file_get_contents(
        dirname(__DIR__, 3) . '/packages/core/stubs/extensao/config.stub',
    );

    expect($stub)->toContain(MakeModuloCommand::MARCADOR_RECURSOS);
});
