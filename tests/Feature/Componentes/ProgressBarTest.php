<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('expõe nome acessível via aria-label quando há label textual', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="60" label="Uso de disco" />');

    // role="progressbar" precisa de um nome acessível (WCAG 4.1.2 — Name, Role, Value);
    // sem ele, leitores de tela anunciam só "60%", sem dizer do quê é o progresso.
    expect($html)->toContain('aria-label="Uso de disco"');
});

it('usa o texto do slot como nome acessível quando não há prop label', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="43">Importação</x-shared.progress-bar>');

    expect($html)->toContain('aria-label="Importação"');
});

it('prioriza o prop aria-label explícito sobre o slot e o label', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="50" label="Visível" aria-label="Nome para leitor de tela" />');

    expect($html)->toContain('aria-label="Nome para leitor de tela"')
        ->and(substr_count($html, 'aria-label='))->toBe(1);
});

it('não emite aria-label quando só há a porcentagem (label === true)', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="65" label />');

    // O valor já é anunciado por aria-valuenow; um aria-label "65%" seria redundante.
    expect($html)->toContain('role="progressbar"')
        ->and($html)->toContain('aria-valuenow="65"')
        ->and($html)->not->toContain('aria-label=');
});

it('não emite aria-label quando não há rótulo nem slot', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="32" variant="danger" />');

    expect($html)->toContain('role="progressbar"')
        ->and($html)->not->toContain('aria-label=');
});

it('striped e animated emitem as classes que o CSS do tema define', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="50" striped animated />');

    // O CSS (resources/css/custom/_progress.css) define .progress-striped e
    // .animate-progress-stripes — as antigas bg-stripes/animate-stripes não existem.
    expect($html)->toContain('progress-striped')
        ->and($html)->toContain('animate-progress-stripes')
        ->and($html)->not->toContain('bg-stripes');
});

it('striped sem animated não anima', function (): void {
    $html = Blade::render('<x-shared.progress-bar :value="50" striped />');

    expect($html)->toContain('progress-striped')
        ->and($html)->not->toContain('animate-progress-stripes');
});
