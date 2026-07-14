<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// O comentário do x-shared.required-indicator define a convenção do design system:
// o asterisco é só visual; a obrigatoriedade real chega à tecnologia assistiva pelo
// required/aria-required DO CONTROLE. O toggle mostrava o indicador (só no modo
// stacked) mas não emitia aria-required no <input> — obrigatoriedade não anunciada.

it('emite aria-required no input e o indicador no modo inline quando obrigatório', function (): void {
    $html = Blade::render('<x-shared.toggle name="ativo" label="Empresa ativa" required />');

    expect($html)
        ->toContain('aria-required="true"')
        ->toContain('>*<'); // indicador do required-indicator (aria-hidden, só visual)
});

it('emite aria-required no input e o indicador no modo stacked quando obrigatório', function (): void {
    $html = Blade::render('<x-shared.toggle name="ativo" label="Empresa ativa" stacked required />');

    expect($html)
        ->toContain('aria-required="true"')
        ->toContain('>*<');
});

it('não emite aria-required nem indicador quando o toggle não é obrigatório', function (): void {
    $html = Blade::render('<x-shared.toggle name="ativo" label="Empresa ativa" />');

    expect($html)
        ->not->toContain('aria-required')
        ->not->toContain('>*<');
});
