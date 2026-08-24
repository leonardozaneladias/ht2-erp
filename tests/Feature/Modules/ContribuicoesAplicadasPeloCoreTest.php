<?php

declare(strict_types=1);

/*
 * Guarda A3 — as contribuições das extensões são responsabilidade do CORE.
 *
 * Até 2026-08-24 o único call site de ModuleRegistry::aplicarContribuicoes()
 * em produção era app/Providers/AppServiceProvider.php, no app do monorepo. Um
 * produto que não copiasse aquela linha — e o skeleton não a garantia — subia
 * sem as permissões e sem os itens de menu de TODAS as extensões instaladas.
 * Sem erro, sem log, sem tela quebrada: as telas simplesmente não apareciam no
 * menu e o gate negava tudo, como se ninguém tivesse permissão.
 *
 * Agora roda no CoreServiceProvider, dentro de booted().
 */

it('o produto não é responsável por aplicar as contribuições', function (): void {
    expect((string) file_get_contents(app_path('Providers/AppServiceProvider.php')))
        ->not->toContain('aplicarContribuicoes');
});

it('as permissões das extensões chegam ao catálogo sem ajuda do produto', function (): void {
    /** @var array<string, array<string, mixed>> $modulos */
    $modulos = (array) config('access.modules');

    expect($modulos)->toHaveKey('negocio')
        ->and($modulos['negocio'])->toHaveKey('rh.funcionarios.listar')
        ->and($modulos['negocio'])->toHaveKey('rh.departamentos.listar');

    // A FiscalBr contribui para outra área, então cobre o segundo pacote.
    expect($modulos)->toHaveKey('tabelas_auxiliares')
        ->and($modulos['tabelas_auxiliares'])->toHaveKey('cnaes.listar');
});

it('os itens de menu das extensões chegam à sidebar sem ajuda do produto', function (): void {
    $secoes = collect((array) config('admin-menu'));

    $negocio = $secoes->firstWhere('key', 'negocio');
    expect($negocio)->not->toBeNull();
    expect(collect($negocio['items'] ?? [])->pluck('key'))
        ->toContain('rh-funcionarios')
        ->toContain('rh-departamentos');

    $auxiliares = $secoes->firstWhere('key', 'tabelas-auxiliares');
    expect($auxiliares)->not->toBeNull();
    expect(collect($auxiliares['items'] ?? [])->pluck('key'))->toContain('ref-cnaes');
});
