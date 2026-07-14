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

it('liga o hint ao controle focável (input color) via aria-describedby, não ao input oculto', function (): void {
    $html = Blade::render(
        '<x-shared.color-picker name="cor" label="Cor" hint="Use o seletor nativo." />',
    );

    // o hint tem o id referenciado
    expect($html)->toContain('id="cor-hint"');

    // o controle REAL focável (input type="color") carrega o aria-describedby —
    // é nele que o leitor de tela anuncia o hint/erro ao focar o campo
    expect($html)->toMatch('/<input\s+type="color"[^>]*aria-describedby="cor-hint"/');

    // o <input type="hidden"> NÃO carrega aria-describedby (não é focável nem
    // anunciado por leitores de tela, então a descrição ali seria inútil)
    expect($html)->not->toMatch('/type="hidden"[^>]*aria-describedby/');
});

it('expõe o botão limpar (clearable) só pelo aria-label, com o ícone marcado como decorativo', function (): void {
    $html = Blade::render(
        '<x-shared.color-picker name="cor" label="Cor" value="#1ab394" clearable />',
    );

    // o botão de limpar é nomeado pelo aria-label (não pelo ícone)
    expect($html)->toContain('aria-label="Limpar cor (herda do tema)"');

    // o ícone tabler--x é puramente decorativo → deve estar escondido do leitor de tela,
    // evitando ruído redundante com o nome já dado pelo aria-label do botão
    expect($html)->toMatch('/<i class="iconify tabler--x[^"]*"[^>]*aria-hidden="true"/');
});

it('não renderiza o botão limpar quando não é clearable', function (): void {
    $html = Blade::render(
        '<x-shared.color-picker name="cor" label="Cor" value="#1ab394" />',
    );

    expect($html)
        ->not->toContain('Limpar cor (herda do tema)')
        ->not->toContain('tabler--x');
});
