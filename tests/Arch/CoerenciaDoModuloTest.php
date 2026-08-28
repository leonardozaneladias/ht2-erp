<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Uma chave, cinco convenções — e um teste (ADR-0021, §3.7 do plano)
|--------------------------------------------------------------------------
|
| A chave de um módulo aparece em cinco lugares que precisam concordar:
|
|   extra.ht2ml.chave  ==  sufixo do diretório do pacote
|                      ==  argumento de ModuleRegistry::modulo()
|                      ==  namespace de view do loadViewsFrom()
|                      ==  nome do arquivo de config
|                      ==  prefixo das permissões
|
| Cinco lugares digitados à mão são cinco chances de divergir, e a divergência
| não faz barulho: uma view com o namespace errado só quebra quando alguém abre
| a tela, e uma permissão com o prefixo errado só aparece quando alguém sem
| super-admin tenta usar. Já aconteceu: o gerador calculava a permissão de
| listagem por snakePlural() enquanto o catálogo usava permissaoBase(), e a tela
| do RH exigia uma permissão inexistente — seletor de empresas vazio, sem erro.
|
| Desde o ModuloBuilder, quatro delas são DERIVADAS de uma. Este teste guarda a
| quinta ponta — o manifesto do pacote — e o dia em que alguém escrever à mão
| algo que hoje é derivado.
|
*/

/** @return list<array{0: string, 1: string, 2: string}> [pasta, chave, dir] */
function modulosDeclarados(): array
{
    $raiz = dirname(__DIR__, 2);
    $modulos = [];

    foreach (glob($raiz . '/packages/*/composer.json') ?: [] as $manifesto) {
        /** @var array<string, mixed> $composer */
        $composer = (array) json_decode((string) file_get_contents($manifesto), true);

        /** @var array<string, string> $ht2ml */
        $ht2ml = (array) (($composer['extra'] ?? [])['ht2ml'] ?? []);

        if (($ht2ml['tipo'] ?? null) !== 'modulo') {
            continue;
        }

        $dir = dirname($manifesto);
        $modulos[] = [basename($dir), (string) ($ht2ml['chave'] ?? ''), $dir];
    }

    return $modulos;
}

it('todo pacote declara o que é em extra.ht2ml', function (): void {
    $raiz = dirname(__DIR__, 2);
    $semDeclaracao = [];

    foreach (glob($raiz . '/packages/*/composer.json') ?: [] as $manifesto) {
        /** @var array<string, mixed> $composer */
        $composer = (array) json_decode((string) file_get_contents($manifesto), true);
        $tipo = (($composer['extra'] ?? [])['ht2ml'] ?? [])['tipo'] ?? null;

        if (! in_array($tipo, ['core', 'modulo', 'biblioteca'], true)) {
            $semDeclaracao[] = basename(dirname($manifesto));
        }
    }

    // A ausência é o problema: um pacote sem extra.ht2ml não é conferido por
    // nenhuma das checagens abaixo, e passaria em silêncio.
    expect($semDeclaracao)->toBe([], implode("\n", [
        'Pacotes sem extra.ht2ml.tipo: ' . implode(', ', $semDeclaracao),
        'Declare "core", "modulo" (com "chave") ou "biblioteca". Ver ADR-0021.',
    ]));
});

it('encontra os módulos declarados', function (): void {
    // Meta-checagem: um glob errado ou uma chave renomeada deixaria o dataset
    // vazio, e os testes abaixo passariam sem ter aberto um arquivo.
    expect(modulosDeclarados())->toHaveCount(3);
});

it('a chave bate com o nome do pacote', function (string $pasta, string $chave): void {
    $prefixo = (string) config('extensoes.prefixo_pacote', 'extensao-');

    expect($chave)->toBe(
        str_starts_with($pasta, $prefixo) ? substr($pasta, strlen($prefixo)) : $pasta,
        "packages/{$pasta} declara a chave '{$chave}', que não bate com o nome do diretório.",
    );
})->with(fn () => modulosDeclarados());

it('a chave bate com a do ModuleRegistry, a do config e a do namespace de view', function (string $pasta, string $chave, string $dir): void {
    $providers = glob($dir . '/src/*ServiceProvider.php') ?: [];

    expect($providers)->not->toBeEmpty("packages/{$pasta} não tem ServiceProvider");

    $provider = (string) file_get_contents($providers[0]);

    // str_contains + toBeTrue, e não toContain: toContain é VARIÁDICO — o
    // segundo argumento vira um segundo needle, não a mensagem. O teste passaria
    // a exigir a própria mensagem de erro dentro do arquivo.
    expect(str_contains($provider, "ModuleRegistry::modulo('{$chave}')"))->toBeTrue(
        "packages/{$pasta}: o registry recebe uma chave diferente de '{$chave}'.",
    );

    // O namespace de view é o que faz `{$chave}::livewire.x` resolver. Divergir
    // aqui só aparece quando alguém abre a tela.
    expect(str_contains($provider, "loadViewsFrom(__DIR__ . '/../resources/views', '{$chave}')"))->toBeTrue(
        "packages/{$pasta}: o namespace de view diverge de '{$chave}'.",
    );

    expect(is_file("{$dir}/config/{$chave}.php"))->toBeTrue(
        "packages/{$pasta}: falta config/{$chave}.php — deConfig('{$chave}') não acharia nada.",
    );
})->with(fn () => modulosDeclarados());

it('as permissões do módulo começam pela chave dele', function (string $pasta, string $chave, string $dir): void {
    /*
     * Exceções conscientes, não descuido.
     *
     * FiscalBr e exemplo-demo nasceram antes da convenção e têm as permissões
     * escritas à mão em config, sem prefixo de módulo. Renomeá-las invalidaria
     * permissões já ATRIBUÍDAS a perfis e personalizações de menu salvas em
     * banco — é migração de dado de produção, não refactor, e por isso não entra
     * junto de uma mudança de convenção.
     *
     * O que a lista garante é que a dívida seja nomeada em vez de tolerada: um
     * pacote novo com o mesmo desvio falha aqui.
     */
    $legado = [
        'extensao-fiscal-br' => ['cnaes.', 'cfops.', 'ncms.'],
        'extensao-exemplo-demo' => ['exemplos.'],
    ];

    $arquivo = "{$dir}/config/{$chave}.php";

    // Sem o config não há o que conferir aqui, e quem reporta a ausência com
    // clareza é o teste anterior. Sem esta guarda, o `require` explode com
    // ErrorException e a saída deixa de dizer qual é o problema real.
    if (! is_file($arquivo)) {
        expect(true)->toBeTrue();

        return;
    }

    /** @var array<string, array<string, mixed>> $config */
    $config = (array) require $arquivo;

    $permissoes = array_keys((array) ($config['permissoes'] ?? []));

    // Recursos declarados pelo builder derivam o prefixo — não há como divergir.
    foreach (array_keys((array) ($config['recursos'] ?? [])) as $recurso) {
        $permissoes[] = "{$chave}.{$recurso}.listar";
    }

    $fora = array_values(array_filter(
        $permissoes,
        static fn (string $p): bool => ! str_starts_with($p, "{$chave}.")
            && ! array_reduce(
                $legado[$pasta] ?? [],
                static fn (bool $ok, string $prefixo): bool => $ok || str_starts_with($p, $prefixo),
                false,
            ),
    ));

    expect($fora)->toBe([], implode("\n", [
        "packages/{$pasta}: permissões fora do prefixo '{$chave}.':",
        ...$fora,
        '',
        'O prefixo é derivado da chave do recurso pelo ModuloBuilder. Se você o',
        'escreveu à mão, provavelmente há uma segunda fórmula discordando da',
        'primeira — foi assim que a tela de Departamentos exigiu uma permissão',
        'que não existia. Ver ADR-0021.',
    ]));
})->with(fn () => modulosDeclarados());
