<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// x-shared.reveal é a entrada padrão de blocos que trocam via @if (etapas de
// wizard, resultados de importação). A animação é CSS pura (token
// --animate-fade-up) — roda 1x quando o nó entra no DOM e nunca replay-a em
// morphs do Livewire; motion-reduce desliga tudo.

it('renderiza o slot com a animação de entrada e o guard de reduced motion', function (): void {
    $html = Blade::render('<x-shared.reveal><p>Conteúdo da etapa</p></x-shared.reveal>');

    expect($html)
        ->toContain('Conteúdo da etapa')
        ->toContain('animate-fade-up')
        ->toContain('motion-reduce:animate-none');
});

it('aplica o atraso de stagger via animation-delay apenas quando informado', function (): void {
    $comDelay = Blade::render('<x-shared.reveal :delay="140">A</x-shared.reveal>');
    $semDelay = Blade::render('<x-shared.reveal>B</x-shared.reveal>');

    expect($comDelay)->toContain('animation-delay: 140ms')
        ->and($semDelay)->not->toContain('animation-delay');
});

it('mescla classes extras do consumidor sem perder a animação', function (): void {
    $html = Blade::render('<x-shared.reveal class="mt-4">C</x-shared.reveal>');

    expect($html)
        ->toContain('mt-4')
        ->toContain('animate-fade-up');
});
