<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// x-shared.step-guide + step-guide-item formam a timeline vertical de
// instruções ("Como funciona") das telas de importação: nó com ícone (ou
// número), linha conectora entre passos e ação contextual embutida por passo
// (slot action) — o botão mora dentro da instrução, não num header distante.

it('renderiza os passos como lista ordenada com título e descrição', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-shared.step-guide>
            <x-shared.step-guide-item icon="tabler--download" title="Baixe o modelo">
                Planilha com cabeçalhos corretos.
            </x-shared.step-guide-item>
            <x-shared.step-guide-item icon="tabler--checklist" title="Confirme" last>
                Nada é gravado sem confirmação.
            </x-shared.step-guide-item>
        </x-shared.step-guide>
        BLADE);

    expect($html)
        ->toContain('<ol')
        ->toContain('Baixe o modelo')
        ->toContain('Planilha com cabeçalhos corretos.')
        ->toMatch('/<span class="iconify tabler--download[^>]*aria-hidden="true"/');
});

it('desenha a linha conectora em todos os passos exceto o último', function (): void {
    $intermediario = Blade::render('<x-shared.step-guide-item title="Meio" :index="1">a</x-shared.step-guide-item>');
    $ultimo = Blade::render('<x-shared.step-guide-item title="Fim" :index="2" last>b</x-shared.step-guide-item>');

    expect($intermediario)->toContain('before:absolute')
        ->and($ultimo)->not->toContain('before:absolute');
});

it('mostra o número no nó quando não há ícone', function (): void {
    $html = Blade::render('<x-shared.step-guide-item title="Passo" :index="3">c</x-shared.step-guide-item>');

    expect($html)
        ->toContain('>3</span>')
        ->not->toContain('iconify');
});

it('embute a ação contextual do passo pelo slot action', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-shared.step-guide-item icon="tabler--download" title="Baixe o modelo">
            Descrição do passo.
            <x-slot:action>
                <button type="button">Baixar planilha modelo</button>
            </x-slot:action>
        </x-shared.step-guide-item>
        BLADE);

    expect($html)->toContain('Baixar planilha modelo');
});
