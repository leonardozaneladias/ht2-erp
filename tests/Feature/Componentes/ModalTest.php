<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('declara aria-modal="true" para confinar o leitor de tela ao diálogo', function (): void {
    $html = Blade::render('<x-shared.modal id="meu-modal" title="Confirmar" />');

    // role="dialog" sozinho não diz ao leitor de tela que o conteúdo fora do
    // diálogo está inerte; aria-modal="true" confina o cursor virtual ao modal.
    expect($html)
        ->toContain('role="dialog"')
        ->toContain('aria-modal="true"');
});

it('mantém os atributos de acessibilidade base do diálogo', function (): void {
    $html = Blade::render('<x-shared.modal id="meu-modal" title="Confirmar" />');

    // Regressão: o diálogo deve continuar rotulado (aria-labelledby) e focável
    // programaticamente (tabindex="-1") junto do aria-modal.
    expect($html)
        ->toContain('aria-labelledby="meu-modal-label"')
        ->toContain('tabindex="-1"');
});

it('esconde do leitor de tela o ícone decorativo do botão fechar', function (): void {
    $html = Blade::render('<x-shared.modal id="meu-modal" title="Confirmar" />');

    // O ícone tabler--x é puramente decorativo: o botão de fechar já tem
    // aria-label="Fechar". Cobertura total: nenhum <i class="iconify ...">
    // pode ficar exposto ao leitor de tela.
    expect($html)->toContain('aria-label="Fechar"');

    preg_match_all('/<i\s+class="[^"]*iconify[^"]*"[^>]*>/', $html, $icones);
    expect($icones[0])->not->toBeEmpty();

    foreach ($icones[0] as $icone) {
        expect($icone)->toContain('aria-hidden="true"');
    }
});
