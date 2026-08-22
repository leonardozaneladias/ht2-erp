<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('anuncia a mudança de força via aria-live no rótulo', function (): void {
    $html = Blade::render('<x-shared.password-strength-meter field-id="password" />');

    // O texto do rótulo muda em runtime ("Digite uma senha" → "Fraca"/"Média"/
    // "Forte") via forms.js; sem aria-live o leitor de tela não anuncia a
    // atualização de força (WCAG 4.1.3 Status Messages).
    expect($html)
        ->toContain('data-password-meter-label')
        ->toContain('aria-live="polite"');
});

it('mantém o data-password-meter-standalone a partir do field-id', function (): void {
    $html = Blade::render('<x-shared.password-strength-meter field-id="password" />');

    // Regressão: o gancho que liga o input ao medidor (forms.js) não pode
    // regredir ao adicionar o aria-live.
    expect($html)
        ->toContain('data-password-meter-standalone="password"')
        ->toContain('data-password-meter-bar');
});
