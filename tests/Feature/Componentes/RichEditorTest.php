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
