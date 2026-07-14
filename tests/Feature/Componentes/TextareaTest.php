<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o valor colado à tag, sem whitespace fantasma', function (): void {
    $html = Blade::render(
        '<x-shared.textarea name="descricao" label="Descrição" :value="$v" />',
        ['v' => 'Conteúdo inicial'],
    );

    // <textarea> preserva o whitespace interno: o valor precisa colar no `>` da
    // tag de abertura. Qualquer newline/indentação entre `<textarea ...>` e o
    // conteúdo vaza para o valor exibido/enviado.
    expect($html)
        ->toContain('>Conteúdo inicial</textarea>')
        ->not->toMatch('/>\s+Conteúdo inicial<\/textarea>/');
});

it('renderiza o textarea vazio realmente vazio (sem whitespace como conteúdo)', function (): void {
    $html = Blade::render('<x-shared.textarea name="obs" label="Observação" />');

    expect($html)->toContain('></textarea>');
});
