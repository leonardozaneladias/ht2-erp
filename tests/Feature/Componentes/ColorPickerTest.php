<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o color-picker com swatch nativo e hex', function (): void {
    $html = Blade::render(
        '<x-shared.color-picker name="cor_primaria" label="Cor primária" value="#1ab394" wire:model="cor_primaria" />',
    );

    expect($html)
        ->toContain('type="color"')
        ->toContain('value="#1ab394"')
        // hex espelhado via Alpine
        ->toContain('x-text="value"')
        // binding repassado via $attributes
        ->toContain('wire:model="cor_primaria"')
        ->toContain('Cor primária');
});

it('renderiza label, hint e normaliza valor vazio para #000000', function (): void {
    $html = Blade::render(
        '<x-shared.color-picker name="cor" label="Cor da marca" value="" hint="Aplicada após salvar." required />',
    );

    expect($html)
        ->toContain('Cor da marca')
        ->toContain('Aplicada após salvar.')
        ->toContain('text-danger') // asterisco de obrigatório
        ->toContain('value="#000000"'); // valor vazio cai no default
});
