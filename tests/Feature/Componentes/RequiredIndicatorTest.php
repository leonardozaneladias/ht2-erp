<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o asterisco decorativo com aria-hidden', function (): void {
    $html = Blade::render('<x-shared.required-indicator />');

    // O asterisco é uma convenção VISUAL de campo obrigatório. A obrigatoriedade
    // é comunicada ao leitor de tela pelo atributo required/aria-required do
    // controle; o glifo "*" não deve ser anunciado como "asterisco" (ruído).
    expect($html)
        ->toContain('aria-hidden="true"')
        ->toContain('text-danger')
        ->toContain('*');
});

it('permite ao consumidor adicionar classes sem duplicar aria-hidden', function (): void {
    $html = Blade::render('<x-shared.required-indicator class="ms-1" />');

    expect($html)
        ->toContain('text-danger')
        ->toContain('ms-1')
        ->toContain('aria-hidden="true"');
    expect(substr_count($html, 'aria-hidden'))->toBe(1);
});

it('marca o indicador required do campo como aria-hidden (via componente base)', function (): void {
    // input representa os controles que consomem o indicador. O asterisco visual
    // não deve ser anunciado; a obrigatoriedade vem do atributo required do input.
    $html = Blade::render('<x-shared.input name="nome" label="Nome" :required="true" />');

    expect($html)
        ->toContain('aria-hidden="true"')
        ->toContain('required');
});

it('não renderiza o indicador quando o campo não é obrigatório', function (): void {
    $html = Blade::render('<x-shared.input name="nome" label="Nome" />');

    // Regressão: sem required, nenhum asterisco decorativo é emitido.
    expect($html)->not->toContain('>*<');
});
