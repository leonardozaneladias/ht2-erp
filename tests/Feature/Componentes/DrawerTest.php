<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('mantém os atributos de acessibilidade base do diálogo lateral', function (): void {
    $html = Blade::render('<x-admin.drawer id="meu-drawer" title="Detalhes" />');

    // Regressão: o painel lateral deve continuar rotulado (aria-labelledby),
    // focável programaticamente (tabindex="-1") e anunciado como diálogo.
    expect($html)
        ->toContain('role="dialog"')
        ->toContain('aria-labelledby="meu-drawer-label"')
        ->toContain('tabindex="-1"');
});

it('rotula o painel sem título com aria-label de fallback', function (): void {
    $html = Blade::render('<x-admin.drawer id="meu-drawer" />');

    // Sem $title não há cabeçalho com <h3 id>; o diálogo precisa de um nome
    // acessível mesmo assim — aria-label="Painel lateral" cobre esse caso.
    expect($html)->toContain('aria-label="Painel lateral"');
});

it('oferece o preset wide para painéis quase tela cheia', function (): void {
    $wide = Blade::render('<x-admin.drawer id="meu-drawer" size="wide" />');
    $padrao = Blade::render('<x-admin.drawer id="meu-drawer" />');

    // wide reserva ~94vw (com teto para telas enormes) — o preset dos painéis
    // de consulta, tipo o dicionário de importação; o default segue compacto.
    expect($wide)->toContain('max-w-[min(120rem,94vw)]')
        ->and($padrao)->toContain('max-w-sm')
        ->and($padrao)->not->toContain('max-w-[min(120rem,94vw)]');
});

it('aplica blur no backdrop via opções do Preline apenas quando pedido', function (): void {
    $comBlur = Blade::render('<x-admin.drawer id="meu-drawer" blur />');
    $semBlur = Blade::render('<x-admin.drawer id="meu-drawer" />');
    $semBackdrop = Blade::render('<x-admin.drawer id="meu-drawer" blur :backdrop="false" />');

    // O Preline lê data-hs-overlay-options do painel ao montar o backdrop;
    // backdropExtraClasses soma o blur sem perder o bg global de _modal.css.
    // Sem backdrop não há elemento para desfocar — a opção não é emitida.
    expect($comBlur)
        ->toContain('data-hs-overlay-options')
        ->toContain('backdropExtraClasses')
        ->toContain('backdrop-blur-md')
        ->and($semBlur)->not->toContain('data-hs-overlay-options')
        ->and($semBackdrop)->not->toContain('data-hs-overlay-options');
});

it('remove padding e scroll do corpo no modo flush', function (): void {
    $flush = Blade::render('<x-admin.drawer id="meu-drawer" flush>conteudo</x-admin.drawer>');
    $padrao = Blade::render('<x-admin.drawer id="meu-drawer">conteudo</x-admin.drawer>');

    // flush entrega o corpo inteiro ao slot (layouts com scrolls próprios,
    // como rail + painel); o default mantém p-5 com scroll vertical.
    expect($flush)->toContain('grow overflow-hidden')
        ->not->toContain('overflow-y-auto p-5')
        ->and($padrao)->toContain('grow overflow-y-auto p-5');
});

it('esconde do leitor de tela o ícone decorativo do botão fechar', function (): void {
    $html = Blade::render('<x-admin.drawer id="meu-drawer" title="Detalhes" />');

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
