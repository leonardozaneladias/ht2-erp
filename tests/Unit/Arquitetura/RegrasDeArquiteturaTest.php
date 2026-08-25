<?php

declare(strict_types=1);

/*
 * As regras de tests/Arch/ só valem se alguém as roda E se elas enxergam
 * classes. Duas falhas silenciosas independentes já aconteceram aqui:
 *
 *  1. COLETA. O tests/Arch.php original existiu por toda a vida do repositório
 *     sem nunca executar — o phpunit.xml não declarava a suíte, o Pest.php não
 *     a coletava e o CI não a invocava.
 *
 *  2. ALVO VAZIO. `expect('HT2ML')` passa em qualquer asserção porque o
 *     Pest\Arch\Support\Composer casa o alvo contra os prefixos PSR-4
 *     registrados, e 'HT2ML' não é um deles ('HT2ML\Core' e 'HT2ML\Rh' são).
 *     Regra que não casa devolve conjunto vazio e fica verde para sempre.
 *
 * Este teste mora em tests/Unit DE PROPÓSITO: dentro de tests/Arch/ a primeira
 * falha o apagaria junto com o que ele existe para detectar.
 */

it('o phpunit.xml declara a suíte Arch', function (): void {
    $phpunit = (string) file_get_contents(base_path('phpunit.xml'));

    expect($phpunit)->toContain('<testsuite name="Arch">')
        ->and($phpunit)->toContain('<directory>tests/Arch</directory>');
});

it('o tests/Pest.php coleta o diretório Arch', function (): void {
    expect((string) file_get_contents(base_path('tests/Pest.php')))
        ->toContain("->in('Arch')");
});

it('o CI e o Makefile rodam a suíte Arch', function (): void {
    expect((string) file_get_contents(base_path('.github/workflows/ci.yml')))
        ->toContain('Unit,Feature,Extensoes,Arch')
        ->and((string) file_get_contents(base_path('Makefile')))
        ->toContain('Unit,Feature,Extensoes,Arch');
});

it('a suíte Arch não está vazia', function (): void {
    $arquivos = glob(base_path('tests/Arch/*Test.php')) ?: [];

    $regras = 0;
    foreach ($arquivos as $arquivo) {
        $regras += substr_count((string) file_get_contents($arquivo), "arch('");
    }

    expect($arquivos)->not->toBeEmpty()
        ->and($regras)->toBeGreaterThanOrEqual(5);
});

it('todo pacote em packages/ entra nas regras de arquitetura', function (): void {
    $pacotes = glob(base_path('packages/*/composer.json')) ?: [];

    expect($pacotes)->not->toBeEmpty();
    expect(namespacesDosPacotes())->toHaveCount(count($pacotes));
});

it('cada namespace alvo casa com um prefixo PSR-4 e enxerga classes', function (string $namespace): void {
    $psr4 = require base_path('vendor/composer/autoload_psr4.php');

    // O Pest\Arch casa o alvo contra as CHAVES do PSR-4. Um namespace que não é
    // chave (nem sub-namespace de uma) resolve para vazio e a regra fica inerte.
    $casaPrefixo = collect(array_keys($psr4))
        ->contains(fn (string $p): bool => str_starts_with($namespace . '\\', $p));

    $classes = collect(array_keys(require base_path('vendor/composer/autoload_classmap.php')))
        ->filter(fn (string $c): bool => str_starts_with($c, $namespace . '\\'));

    expect($casaPrefixo)->toBeTrue("'{$namespace}' não casa com nenhum prefixo PSR-4 — a regra de arquitetura passaria vazia.")
        ->and($classes)->not->toBeEmpty("'{$namespace}' não tem classe alguma no classmap.");
})->with(fn () => namespacesDosPacotes());

it('nenhum expect() literal das regras de arquitetura mira namespace inexistente', function (): void {
    // Só o PSR-4, e de propósito: é exatamente o que o Pest\Arch consulta.
    // Conferir contra o classmap ANULARIA a checagem — 'HT2ML\Core\Foo' começa
    // com 'HT2ML\', então o alvo inerte pareceria válido. (Erro cometido na
    // primeira versão desta guarda, e pego por provar que ela falha quando deve.)
    $psr4 = array_keys(require base_path('vendor/composer/autoload_psr4.php'));

    $inertes = [];

    foreach (glob(base_path('tests/Arch/*.php')) ?: [] as $arquivo) {
        preg_match_all(
            "/->expect\(\s*'([A-Za-z_][A-Za-z0-9_\\\\]*)'/",
            (string) file_get_contents($arquivo),
            $achados,
        );

        foreach ($achados[1] as $alvo) {
            $prefixo = $alvo . '\\';

            // O alvo vale se for uma chave PSR-4 ou sub-namespace de uma:
            // 'HT2ML\Core\Models\Referencia\' começa com a chave 'HT2ML\Core\';
            // 'HT2ML\' não começa com chave alguma.
            $casa = collect($psr4)->contains(fn (string $p): bool => str_starts_with($prefixo, $p));

            if (! $casa) {
                $inertes[] = basename($arquivo) . ": expect('{$alvo}')";
            }
        }
    }

    // `expect('HT2ML')` é o caso real: 'HT2ML' não é prefixo PSR-4 (as chaves são
    // 'HT2ML\Core\', 'HT2ML\Rh\'…), então a regra passa sem inspecionar nada.
    expect($inertes)->toBe([], 'Regra(s) de arquitetura mirando namespace que não resolve — passariam vazias: ' . implode(', ', $inertes));
});
