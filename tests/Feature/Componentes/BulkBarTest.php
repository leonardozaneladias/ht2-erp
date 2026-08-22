<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('anuncia a seleção em massa como live region (role="status")', function (): void {
    $html = Blade::render('<x-admin.table.bulk-bar :count="3" />');

    // A barra surge dinamicamente (Livewire re-renderiza quando count passa de 0 -> N
    // ao selecionar linhas). Sem uma live region, leitores de tela não anunciam o
    // aparecimento da seleção em massa (WCAG 4.1.3 - Status Messages). role="status"
    // (polite) anuncia a contagem sem interromper, alinhado ao x-admin.unsaved-bar.
    expect($html)->toContain('role="status"');
});

it('mantém a contagem, o label e as ações (slot) ao renderizar com itens selecionados', function (): void {
    $html = Blade::render(
        '<x-admin.table.bulk-bar :count="3" label="selecionado(s)"><button wire:click="excluir">Excluir</button></x-admin.table.bulk-bar>',
    );

    expect($html)
        ->toMatch('/>\s*3\s*</') // badge de contagem (tolerante a whitespace do Blade)
        ->toContain('selecionado(s)')
        ->toContain('wire:click="excluir"')
        ->not->toContain('sr-only'); // barra visível quando há seleção (live region não oculta)
});

it('usa o label customizado fornecido', function (): void {
    $html = Blade::render('<x-admin.table.bulk-bar :count="2" label="registros marcados" />');

    expect($html)
        ->toContain('registros marcados')
        ->toMatch('/>\s*2\s*</');
});

it('mantém a live region presente (porém vazia e oculta) quando não há itens selecionados', function (): void {
    $html = Blade::render('<x-admin.table.bulk-bar :count="0" />');

    // PEND-16: a live region (role="status") fica SEMPRE no DOM — vazia e sr-only quando
    // count=0 — para que o leitor de tela a registre ao montar a tabela e anuncie a seleção
    // de forma confiável quando ela entra (count>0), inclusive em leitores que não detectam
    // regiões recém-inseridas (WCAG 4.1.3 - Status Messages).
    expect($html)
        ->toContain('role="status"') // live region presente desde o início
        ->toContain('sr-only') // oculta visualmente, sem a barra
        ->not->toContain('selecionado'); // sem mensagem enquanto não há seleção
});
