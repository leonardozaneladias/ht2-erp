<?php

declare(strict_types=1);

use HT2ML\Core\Support\Generator\ResolvedorDeStubs;

/*
 * Os stubs do gerador precisam VIAJAR NO PACOTE.
 *
 * Até 2026-08-24 eles viviam em `stubs/` na raiz do monorepo e o comando os lia
 * com base_path('stubs/modulo'). Num produto instalado por Composer,
 * `find vendor/ht2ml/core -name '*.stub'` devolvia VAZIO: o make:modulo morria
 * em MakeModuloCommand.php:76 antes de escrever um byte. O caminho documentado
 * em docs/criar-modulo.md estava morto exatamente onde as 20 telas do EduConecta
 * iam nascer.
 *
 * Note que este teste roda no monorepo, onde base_path('stubs/modulo') NÃO
 * existe mais — ou seja, ele exercita o mesmo caminho de resolução que um
 * produto usaria.
 */

it('todos os stubs de módulo são encontrados dentro do pacote', function (): void {
    $stubs = new ResolvedorDeStubs('modulo');

    $doPacote = glob(dirname(__DIR__, 3) . '/packages/core/stubs/modulo/*.stub') ?: [];

    expect($doPacote)->toHaveCount(19);

    foreach ($doPacote as $arquivo) {
        expect($stubs->existe(basename($arquivo)))->toBeTrue();
    }
});

it('todos os stubs de extensão são encontrados dentro do pacote', function (): void {
    $stubs = new ResolvedorDeStubs('extensao');

    $doPacote = glob(dirname(__DIR__, 3) . '/packages/core/stubs/extensao/*.stub') ?: [];

    expect($doPacote)->toHaveCount(5);

    foreach ($doPacote as $arquivo) {
        expect($stubs->existe(basename($arquivo)))->toBeTrue();
    }
});

it('o produto vem antes do pacote na ordem de busca', function (): void {
    $diretorios = (new ResolvedorDeStubs('modulo'))->diretorios();

    expect($diretorios)->toHaveCount(2)
        ->and($diretorios[0])->toBe(base_path('stubs/modulo'))
        ->and(realpath($diretorios[1]))->toBe(dirname(__DIR__, 3) . '/packages/core/stubs/modulo');
});

it('o produto sobrescreve um stub sem herdar os outros dezoito', function (): void {
    $dir = base_path('stubs/modulo');
    $arquivo = $dir . '/livewire-table.stub';
    $jaExistia = is_dir($dir);

    try {
        mkdir($dir, 0o755, true);
        file_put_contents($arquivo, '// stub do produto');

        $stubs = new ResolvedorDeStubs('modulo');

        expect($stubs->conteudo('livewire-table.stub'))->toBe('// stub do produto')
            // Os demais continuam vindo do núcleo — é o ponto da precedência
            // por arquivo: sobrescrever um não obriga a copiar os dezenove.
            ->and($stubs->caminho('model.stub'))->toContain('/packages/core/stubs/modulo/');
    } finally {
        @unlink($arquivo);
        if (! $jaExistia) {
            @rmdir($dir);
        }
    }
});

it('um stub inexistente falha com mensagem que diz onde procurou', function (): void {
    expect(fn () => (new ResolvedorDeStubs('modulo'))->caminho('nao-existe.stub'))
        ->toThrow(RuntimeException::class, 'nao-existe.stub');
});
