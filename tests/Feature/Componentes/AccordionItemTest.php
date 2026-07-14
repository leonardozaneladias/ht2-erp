<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade do x-shared.accordion-item (família accordion):
//
// 1) Ícones decorativos — o chevron de estado (tabler--chevron-down) e o ícone
//    opcional de título são puramente visuais: o estado aberto/fechado já é
//    anunciado por aria-expanded no botão, e o nome da seção vem do texto do
//    título. Ambos recebem aria-hidden="true" (mesma convenção de
//    input/button/dropdown-item/kpi-card/file-upload/password-input/date-picker).
//
// 2) Rótulo da região — o painel (role="region") deve ser rotulado pelo BOTÃO que
//    o controla (padrão WAI-ARIA Accordion), não pelo wrapper inteiro. Antes
//    aria-labelledby apontava para o id do wrapper, que contém o próprio painel —
//    rótulo circular/ruidoso. Agora o botão tem id próprio e a região aponta para
//    ele, de modo que o nome acessível da região é só o texto do título.
//
// Caracteriza também os contratos do toggle (aria-controls/aria-expanded), do
// painel (id derivado, role region) e dos estados open/fechado, até então sem teste.

it('o chevron de estado recebe aria-hidden', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="faq-1" title="Pergunta" />');

    expect($html)->toMatch('/<i\s+class="iconify tabler--chevron-down[^"]*"[^>]*aria-hidden="true"/s');
});

it('o ícone de título recebe aria-hidden quando informado', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="sec-1" title="Dados" icon="tabler--user" />');

    expect($html)->toMatch('/<i class="iconify tabler--user[^"]*"[^>]*aria-hidden="true"/');
});

it('a região é rotulada pelo botão que a controla, não pelo wrapper (aria-labelledby)', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="efe-rh" title="RH" />');

    // o botão de toggle ganha id próprio…
    expect($html)
        ->toContain('id="accordion-trigger-efe-rh"')
        // …e a região aponta o aria-labelledby para ele (não para o id do wrapper "efe-rh")
        ->toContain('aria-labelledby="accordion-trigger-efe-rh"')
        ->not->toContain('aria-labelledby="efe-rh"');
});

it('mantém a estrutura toggle↔painel (regressão)', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="bloco" title="Título da seção" />');

    expect($html)
        ->toContain('id="bloco"')                       // id do wrapper preservado (âncora/uso externo)
        ->toContain('hs-accordion')                      // hook do Preline
        ->toContain('aria-controls="accordion-panel-bloco"')
        ->toContain('id="accordion-panel-bloco"')        // painel com id derivado
        ->toContain('role="region"')
        ->toContain('Título da seção');                  // título textual
});

it('open reflete em active e aria-expanded true com painel visível (regressão)', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="abr" title="Aberto" :open="true" />');

    expect($html)
        ->toContain('active')
        ->toContain('aria-expanded="true"')
        ->not->toContain('hs-accordion-content border-default-300 w-full overflow-hidden border-t transition-[height] duration-300 hidden');
});

it('fechado por padrão esconde o painel e marca aria-expanded false (regressão)', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="fch" title="Fechado" />');

    expect($html)
        ->toContain('aria-expanded="false"')
        ->toContain('hidden');
});

it('sem icon não renderiza ícone de título — apenas o chevron (regressão)', function (): void {
    $html = Blade::render('<x-shared.accordion-item id="sem-icone" title="Só título" />');

    // só o chevron usa a classe iconify quando não há ícone de título
    expect(substr_count($html, 'class="iconify'))->toBe(1);
});
