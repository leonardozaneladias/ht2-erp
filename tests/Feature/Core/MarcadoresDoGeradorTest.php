<?php

declare(strict_types=1);

use HT2ML\Core\Console\Commands\MakeRecursoCommand;

/*
 * O gerador escreve nos arquivos do pacote procurando comentários-marcador: um
 * no config (a entrada em 'recursos'), um no routes/admin.php (o grupo de
 * rotas) e um no ServiceProvider (componentes Livewire e Gate::policy). Sem o
 * marcador, o bloco não entra.
 *
 * O que fazia disso um bug e não um inconveniente era o silêncio. Até
 * 2026-08-24 a injeção de config era pulada e o comando reportava "criado
 * (permissões + menu)" assim mesmo; consertado aquilo, sobraram duas piores: o
 * registro no provider voltava sem dizer nada, e as duas injeções de rota
 * chamavam str_replace sem casar nada, reescreviam o arquivo idêntico e AINDA
 * anunciavam "criado (rotas)". Nos três casos o resultado é uma tela gerada e
 * INALCANÇÁVEL — 404 por falta de rota, 403 por falta de permissão — e nada
 * ficava vermelho.
 *
 * Medido: fiscal-br e exemplo-demo tinham config com marcador, mas routes e
 * provider sem. Gerar um recurso ali produzia 19 arquivos e nenhuma tela.
 *
 * Um pacote novo nasce certo, porque os stubs trazem os três. Este teste existe
 * para quem reescreve um arquivo desses à mão e apaga o marcador sem perceber —
 * e lê o texto do marcador das constantes do próprio comando, para não repetir
 * a divergência de quando os marcadores mudaram e o teste continuou pinando os
 * nomes velhos.
 *
 * Pacote SEM config é extensão-biblioteca (ex.: documentos): não carrega
 * módulo, não tem tela, não precisa de marcador nenhum.
 */

/** @return list<array{0: string, 1: string, 2: string}> [pacote, o que é, caminho] */
function marcadoresDePacote(): array
{
    $raiz = dirname(__DIR__, 3);
    $linhas = [];

    foreach (glob($raiz . '/packages/extensao-*/config/*.php') ?: [] as $config) {
        $dir = dirname($config, 2);
        $pacote = basename($dir);
        $providers = glob($dir . '/src/*ServiceProvider.php') ?: [];

        $linhas[] = [$pacote, 'recursos (config)', $config];
        $linhas[] = [$pacote, 'rotas (routes/admin.php)', $dir . '/routes/admin.php'];
        $linhas[] = [$pacote, 'provider (ServiceProvider)', $providers[0] ?? $dir . '/src/AUSENTE.php'];
    }

    return $linhas;
}

/** @return array<string, string> rótulo => marcador esperado */
function marcadorEsperado(): array
{
    return [
        'recursos (config)' => MakeRecursoCommand::MARCADOR_RECURSOS,
        'rotas (routes/admin.php)' => MakeRecursoCommand::MARCADOR_ROTAS,
        'provider (ServiceProvider)' => MakeRecursoCommand::MARCADOR_PROVIDER,
    ];
}

it('encontra os pacotes que carregam módulo', function (): void {
    // Meta-checagem: um glob errado deixaria o dataset vazio e o guard passaria
    // sem nunca ter aberto um arquivo.
    expect(marcadoresDePacote())->toHaveCount(9);
});

it('o pacote tem o marcador que o gerador procura', function (string $pacote, string $oQue, string $caminho): void {
    expect(is_file($caminho))->toBeTrue("{$pacote}: {$caminho} não existe");

    expect((string) file_get_contents($caminho))
        ->toContain(marcadorEsperado()[$oQue]);
})->with(fn () => marcadoresDePacote());

it('os stubs entregam os três marcadores a um pacote novo', function (string $stub, string $marcador): void {
    $conteudo = (string) file_get_contents(
        dirname(__DIR__, 3) . "/packages/core/stubs/extensao/{$stub}",
    );

    expect($conteudo)->toContain($marcador);
})->with([
    ['config.stub', MakeRecursoCommand::MARCADOR_RECURSOS],
    ['routes.stub', MakeRecursoCommand::MARCADOR_ROTAS],
    ['service-provider.stub', MakeRecursoCommand::MARCADOR_PROVIDER],
]);
