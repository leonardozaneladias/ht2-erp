<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

/*
 * As quatro views do PowerGrid sobrescritas pelo núcleo.
 *
 * Elas trocam o <select> nativo (e o TomSelect) pelo x-shared.combobox, que é o
 * que dá filtro pesquisável e multi-seleção nas tabelas. Viviam em
 * resources/views/vendor/ do app, e por isso JÁ FORAM copiadas byte a byte para
 * o EduConecta antes deste conserto: o terceiro produto esqueceria de copiá-las
 * e perderia os filtros sem nenhum erro aparecer.
 *
 * O risco que este teste cobre é o `composer update` do PowerGrid mudar o
 * caminho de uma view: hoje isso quebraria em SILÊNCIO — o Blade resolveria a
 * view do vendor e o filtro voltaria a ser um <select> comum.
 */

/** @return list<string> */
function viewsSobrescritasDoPowerGrid(): array
{
    return [
        'livewire-powergrid::components.inputs.select',
        'livewire-powergrid::components.frameworks.tailwind.filters.boolean',
        'livewire-powergrid::components.frameworks.tailwind.filters.select',
        'livewire-powergrid::components.frameworks.tailwind.filters.input-text',
    ];
}

it('resolve para o arquivo do núcleo, não para o do vendor', function (string $view): void {
    // realpath: o finder devolve o caminho como foi registrado
    // ('.../packages/core/src/../resources/views/...'), com o '..' sem resolver.
    $caminho = (string) realpath(View::getFinder()->find($view));

    expect($caminho)->toContain('/packages/core/resources/views/livewire-powergrid/')
        ->and($caminho)->not->toContain('/vendor/power-components/');
})->with(fn () => viewsSobrescritasDoPowerGrid());

it('a view do núcleo usa o combobox próprio, e não o select do vendor', function (string $view): void {
    $conteudo = (string) file_get_contents(View::getFinder()->find($view));

    expect($conteudo)->toContain('x-shared.combobox');
})->with(fn () => viewsSobrescritasDoPowerGrid());

it('o produto vem antes do núcleo, que vem antes do vendor', function (): void {
    $hints = collect(View::getFinder()->getHints()['livewire-powergrid'] ?? [])
        ->map(fn (string $h): string => (string) (realpath($h) ?: $h))
        ->all();

    $posicaoCore = collect($hints)->search(
        fn (string $h): bool => str_contains($h, '/packages/core/resources/views/livewire-powergrid'),
    );
    $posicaoVendor = collect($hints)->search(
        fn (string $h): bool => str_contains($h, '/vendor/power-components/'),
    );

    expect($posicaoCore)->not->toBeFalse('O núcleo não está registrado no namespace livewire-powergrid.')
        ->and($posicaoVendor)->not->toBeFalse();

    // Núcleo antes do vendor: senão o override nunca venceria.
    expect($posicaoCore)->toBeLessThan($posicaoVendor);

    // E qualquer caminho do produto vem antes do núcleo: um produto precisa
    // poder restilizar UM filtro sem herdar a manutenção dos quatro.
    $caminhosDoProduto = array_map(
        fn (string $p): string => (string) (realpath($p) ?: $p),
        (array) config('view.paths'),
    );

    foreach ($hints as $i => $hint) {
        $ehDoProduto = (bool) array_filter(
            $caminhosDoProduto,
            static fn (string $base): bool => str_starts_with($hint, rtrim($base, '/') . '/'),
        );

        if ($ehDoProduto) {
            expect($i)->toBeLessThan($posicaoCore);
        }
    }
});

it('o app do monorepo não mantém cópia própria das quatro views', function (): void {
    expect(is_dir(resource_path('views/vendor/livewire-powergrid')))->toBeFalse(
        'As views voltaram para o app. Elas moram em packages/core e chegam por Composer; '
        . 'uma cópia aqui volta a divergir entre produtos no primeiro composer update.',
    );
});
