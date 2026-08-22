<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// x-shared.stat-tile é o tile de métrica dos resultados de importação/lotes:
// ícone soft por variant, label uppercase e valor tabular-nums com contagem
// animada (rAF com guard de prefers-reduced-motion, herdado do partial de
// tiles do RH que ele substituiu).

it('renderiza label, valor formatado pt-BR e ícone decorativo', function (): void {
    $html = Blade::render('<x-shared.stat-tile label="Total de linhas" :value="1250" icon="tabler--sum" />');

    expect($html)
        ->toContain('Total de linhas')
        ->toContain('1.250')
        ->toMatch('/<span class="iconify tabler--sum[^>]*aria-hidden="true"/');
});

it('mapeia as variants semânticas para o círculo soft e a cor do valor', function (): void {
    $success = Blade::render('<x-shared.stat-tile label="Criados" :value="10" variant="success" />');
    $default = Blade::render('<x-shared.stat-tile label="Total" :value="10" />');

    expect($success)
        ->toContain('bg-success/12 text-success')
        ->and($default)->toContain('bg-light text-default-500');
});

it('a contagem animada respeita prefers-reduced-motion e pode ser desligada', function (): void {
    $animado = Blade::render('<x-shared.stat-tile label="Criados" :value="10" />');
    $estatico = Blade::render('<x-shared.stat-tile label="Criados" :value="10" :count-up="false" />');

    expect($animado)
        ->toContain('prefers-reduced-motion')
        ->toContain('requestAnimationFrame')
        ->and($estatico)->not->toContain('requestAnimationFrame');
});

it('aplica o atraso de stagger via animation-delay apenas quando informado', function (): void {
    $comDelay = Blade::render('<x-shared.stat-tile label="Criados" :value="10" :delay="70" />');
    $semDelay = Blade::render('<x-shared.stat-tile label="Criados" :value="10" />');

    expect($comDelay)->toContain('animation-delay: 70ms')
        ->and($semDelay)->not->toContain('animation-delay');
});
