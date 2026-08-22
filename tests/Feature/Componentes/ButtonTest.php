<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — ícones decorativos não devem ser anunciados pelo leitor de tela.
// O nome acessível do botão vem SEMPRE do texto (slot) ou do aria-label (modo
// icon-only), nunca do ícone; logo todo ícone/spinner é decorativo e recebe
// aria-hidden="true" (mesma convenção já adotada em unsaved-bar/breadcrumb/etc.).

it('marca o ícone leading como decorativo (aria-hidden) quando há rótulo', function (): void {
    $html = Blade::render('<x-shared.button icon="tabler--plus">Novo</x-shared.button>');

    expect($html)
        ->toMatch('/<i class="iconify tabler--plus[^>]*aria-hidden="true"/')
        ->toContain('Novo');
});

it('marca o ícone trailing como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-shared.button icon-right="tabler--arrow-right">Avançar</x-shared.button>');

    expect($html)->toMatch('/<i class="iconify tabler--arrow-right[^>]*aria-hidden="true"/');
});

it('marca o spinner de loading como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-shared.button :loading="true">Salvando</x-shared.button>');

    // O label ("Salvando") segue anunciado; o spinner girando é puramente visual.
    expect($html)
        ->toMatch('/tabler--loader-2[^>]*aria-hidden="true"/')
        ->toContain('Salvando');
});

it('no modo icon-only mantém o nome acessível pelo aria-label e esconde o ícone', function (): void {
    $html = Blade::render('<x-shared.button icon="tabler--trash" :icon-only="true">Excluir</x-shared.button>');

    // Nome acessível vem do aria-label + texto sr-only; o ícone fica aria-hidden.
    expect($html)
        ->toContain('aria-label="Excluir"')
        ->toContain('sr-only')
        ->toMatch('/<i class="iconify tabler--trash[^>]*aria-hidden="true"/');
});

it('renderiza um <a> com href quando href é informado (regressão)', function (): void {
    $html = Blade::render('<x-shared.button href="/admin/x">Ir</x-shared.button>');

    expect($html)
        ->toContain('<a')
        ->toContain('href="/admin/x"');
});

it('respeita o type passado no modo button (regressão)', function (): void {
    $html = Blade::render('<x-shared.button type="submit">Enviar</x-shared.button>');

    expect($html)
        ->toContain('<button')
        ->toContain('type="submit"');
});

it('desabilita o botão quando disabled/loading (regressão)', function (): void {
    $html = Blade::render('<x-shared.button :disabled="true">Bloqueado</x-shared.button>');

    expect($html)->toContain('disabled');
});
