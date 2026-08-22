<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('anuncia as alterações pendentes como live region (role="status")', function (): void {
    $html = Blade::render('<x-admin.unsaved-bar :count="3" />');

    // A barra surge dinamicamente (Livewire re-renderiza quando count passa de 0 -> N).
    // Sem uma live region, leitores de tela não anunciam o aparecimento das alterações
    // não salvas (WCAG 4.1.3 - Status Messages). role="status" (polite) anuncia a
    // mensagem sem interromper, alinhado ao x-shared.toast e ao x-shared.spinner.
    expect($html)->toContain('role="status"');
});

it('mantém a mensagem e as ações ao renderizar com alterações pendentes', function (): void {
    $html = Blade::render('<x-admin.unsaved-bar :count="3" save="salvar" discard="descartar" />');

    expect($html)
        ->toContain('3 alterações não salvas')
        ->toContain('wire:click="salvar"')
        ->toContain('wire:click="descartar"')
        ->not->toContain('sr-only'); // barra visível quando há alterações (live region não oculta)
});

it('usa o singular quando há exatamente uma alteração', function (): void {
    $html = Blade::render('<x-admin.unsaved-bar :count="1" />');

    expect($html)->toContain('1 alteração não salva');
});

it('mantém a live region presente (porém vazia e oculta) quando não há alterações pendentes', function (): void {
    $html = Blade::render('<x-admin.unsaved-bar :count="0" />');

    // PEND-16: a live region (role="status") fica SEMPRE no DOM — vazia e sr-only quando
    // count=0 — para que o leitor de tela a registre ao montar a tela e anuncie a mensagem
    // de forma confiável quando ela entra (count>0), inclusive em leitores que não detectam
    // regiões recém-inseridas (WCAG 4.1.3 - Status Messages).
    expect($html)
        ->toContain('role="status"') // live region presente desde o início
        ->toContain('sr-only') // oculta visualmente, sem a barra
        ->not->toContain('alteração') // sem mensagem enquanto não há pendências
        ->not->toContain('wire:click'); // sem botões de ação
});
