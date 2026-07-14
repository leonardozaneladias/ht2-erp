<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Mesma convenção do required-indicator: o checkbox passa a anunciar a
// obrigatoriedade ao leitor de tela via aria-required no <input>, além do
// asterisco visual no rótulo.

it('emite aria-required no input e o indicador quando obrigatório', function (): void {
    $html = Blade::render('<x-shared.checkbox name="aceite" label="Aceito os termos" required />');

    expect($html)
        ->toContain('aria-required="true"')
        ->toContain('>*<');
});

it('não emite aria-required nem indicador quando não é obrigatório', function (): void {
    $html = Blade::render('<x-shared.checkbox name="aceite" label="Aceito os termos" />');

    expect($html)
        ->not->toContain('aria-required')
        ->not->toContain('>*<');
});
