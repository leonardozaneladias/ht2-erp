<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renderiza o rich-editor com container Quill e textarea oculto (wire:ignore)', function (): void {
    $html = Blade::render(
        '<x-shared.rich-editor name="mensagem" label="Mensagem" wire:model="mensagem" :value="$v" required />',
        ['v' => '<p>conteúdo inicial</p>'],
    );

    expect($html)
        ->toContain('data-af-quill')
        ->toContain('data-af-quill-editor')
        ->toContain('data-af-quill-input')
        // a árvore do Quill é protegida dos morphs do Livewire
        ->toContain('wire:ignore')
        // binding repassado ao textarea via $attributes
        ->toContain('wire:model="mensagem"')
        ->toContain('Mensagem')
        // conteúdo inicial chega ao textarea (escapado)
        ->toContain('conteúdo inicial');
});

it('renderiza o valor colado à tag do textarea oculto, sem whitespace fantasma', function (): void {
    $html = Blade::render(
        '<x-shared.rich-editor name="mensagem" label="Mensagem" :value="$v" />',
        ['v' => 'Texto inicial'],
    );

    // O <textarea> oculto preserva o whitespace interno: o valor precisa colar no `>`
    // da tag de abertura. Qualquer newline/indentação entre `<textarea ...>` e o
    // conteúdo vaza para o que o Quill lê (POST tradicional / pré-JS) e para o old()
    // em re-render. Mesmo bug corrigido em x-shared.textarea (ver TextareaTest).
    expect($html)
        ->toContain('>Texto inicial</textarea>')
        ->not->toMatch('/>\s+Texto inicial<\/textarea>/');
});

it('renderiza o textarea oculto realmente vazio quando não há valor', function (): void {
    $html = Blade::render('<x-shared.rich-editor name="mensagem" label="Mensagem" />');

    expect($html)->toContain('></textarea>');
});

it('expõe o gancho para nomear o editor visível do Quill (aria-labelledby)', function (): void {
    // O controle real focável é o .ql-editor (contenteditable) que o Quill cria
    // em runtime; o <label for> aponta para o <textarea> oculto. Para o JS
    // (initRichEditors) associar o label ao editor visível via aria-labelledby,
    // o <label> ganha um id estável e o wrapper expõe esse id como data-attribute.
    $html = Blade::render('<x-shared.rich-editor name="mensagem" label="Mensagem" />');

    expect($html)
        ->toContain('id="mensagem-label"')
        ->toContain('for="mensagem"')
        ->toContain('data-af-quill-labelledby="mensagem-label"');
});

it('deriva o id do label a partir do name normalizado', function (): void {
    // Mesmo normalizador do $fieldId (colchetes/pontos viram hífen).
    $html = Blade::render('<x-shared.rich-editor name="dados[corpo]" label="Corpo" />');

    expect($html)
        ->toContain('id="dados-corpo-label"')
        ->toContain('data-af-quill-labelledby="dados-corpo-label"');
});

it('não expõe o gancho de aria-labelledby quando não há label', function (): void {
    // Sem label não há o que associar — não cria referência pendente.
    $html = Blade::render('<x-shared.rich-editor name="mensagem" />');

    expect($html)->not->toContain('data-af-quill-labelledby');
});
