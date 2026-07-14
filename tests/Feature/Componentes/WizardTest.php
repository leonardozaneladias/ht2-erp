<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('marca apenas o passo atual com aria-current="step"', function (): void {
    $html = Blade::render('<x-shared.wizard :steps="[\'Enviar\', \'Analisar\', \'Concluído\']" :current="2" />');

    expect(substr_count($html, 'aria-current="step"'))->toBe(1)
        ->and($html)->toContain('Enviar')
        ->and($html)->toContain('Analisar')
        ->and($html)->toContain('Concluído');
});

it('apenas os passos concluídos ganham o ícone de check', function (): void {
    $html = Blade::render('<x-shared.wizard :steps="[\'Um\', \'Dois\', \'Três\']" :current="3" />');

    // Passos 1 e 2 concluídos viram check; o atual (3) segue numerado.
    expect(substr_count($html, 'tabler--check'))->toBe(2);
});

it('aceita passos como mapas com ícone opcional', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-shared.wizard
            :steps="[['label' => 'Envio', 'icon' => 'tabler--upload'], ['label' => 'Análise']]"
            :current="1"
        />
        BLADE);

    expect($html)->toContain('tabler--upload')
        ->and($html)->toContain('Envio')
        ->and($html)->toContain('Análise');
});

it('normaliza o passo atual para dentro do intervalo', function (): void {
    $abaixo = Blade::render('<x-shared.wizard :steps="[\'A\', \'B\']" :current="0" />');
    $acima = Blade::render('<x-shared.wizard :steps="[\'A\', \'B\']" :current="9" />');

    expect($abaixo)->toContain('Etapa 1 de 2')
        ->and($acima)->toContain('Etapa 2 de 2');
});

it('não renderiza nada com a lista de passos vazia', function (): void {
    $html = Blade::render('<x-shared.wizard :steps="[]" :current="1" />');

    expect(trim($html))->toBe('');
});

it('exibe o fallback compacto com barra de progresso para telas pequenas', function (): void {
    $html = Blade::render('<x-shared.wizard :steps="[\'Enviar\', \'Analisar\', \'Confirmar\', \'Concluído\']" :current="3" />');

    expect($html)->toContain('Etapa 3 de 4')
        ->and($html)->toContain('role="progressbar"')
        ->and($html)->toContain('aria-label="Etapa 3 de 4"');
});

it('renderiza o slot quando informado e o omite quando vazio', function (): void {
    $comSlot = Blade::render('<x-shared.wizard :steps="[\'A\', \'B\']" :current="1">Conteúdo da etapa</x-shared.wizard>');
    $semSlot = Blade::render('<x-shared.wizard :steps="[\'A\', \'B\']" :current="1" />');

    expect($comSlot)->toContain('Conteúdo da etapa')
        ->and($semSlot)->not->toContain('mt-6');
});
