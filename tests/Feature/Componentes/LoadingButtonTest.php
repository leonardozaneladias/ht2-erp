<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// O x-shared.loading-button envolve o x-shared.button e, DENTRO do slot, renderiza
// seus PRÓPRIOS ícones (esquerdo, direito e o spinner de wire:loading). Eles não
// herdam o aria-hidden que o button aplica aos ícones que ELE mesmo renderiza (o
// loading-button não passa :icon/:icon-right ao button, desenha-os no slot).
// Todos os três são puramente decorativos: o botão é sempre anunciado pelo texto do
// slot (sr-only quando icon-only) e o estado de loading é comunicado por
// wire:loading.attr="disabled" + o loadingText/label textual. Marcá-los como
// aria-hidden remove ruído redundante do leitor de tela em todo botão de ação async.

it('marca os ícones decorativos (esquerdo, direito e spinner) como aria-hidden', function (): void {
    $html = Blade::render(
        '<x-shared.loading-button icon="tabler--device-floppy" icon-right="tabler--arrow-right">Salvar</x-shared.loading-button>',
    );

    // 3 ícones do loading-button: <i> esquerdo, <i> direito e o spinner tabler--loader-2.
    // Nenhum outro <i> iconify deve restar exposto ao leitor de tela.
    preg_match_all('/<i\b[^>]*iconify[^>]*>/', $html, $todos);
    preg_match_all('/<i\b[^>]*iconify[^>]*aria-hidden="true"[^>]*>/', $html, $comHidden);

    expect($todos[0])->toHaveCount(3);
    expect($comHidden[0])->toHaveCount(count($todos[0]));
});

it('mantém o spinner de loading marcado como decorativo', function (): void {
    // O spinner sempre é renderizado (camada wire:loading); o estado é comunicado
    // por wire:loading.attr="disabled" + o texto de loading, não pelo ícone.
    $html = Blade::render('<x-shared.loading-button>Processando</x-shared.loading-button>');

    expect($html)->toMatch('/<i\b[^>]*tabler--loader-2[^>]*aria-hidden="true"[^>]*>/');
});
