<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza a imagem quando há src', function (): void {
    $html = Blade::render('<x-shared.avatar name="João Silva" src="https://cdn/x.jpg" />');

    expect($html)->toContain('<img')->toContain('https://cdn/x.jpg');
});

it('renderiza iniciais quando não há src', function (): void {
    $html = Blade::render('<x-shared.avatar name="João Silva" />');

    expect($html)->not->toContain('<img')->toContain('JS');
});
