<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('associa o gatilho ao balão via aria-describedby usando o id informado', function (): void {
    $html = Blade::render('<x-shared.tooltip id="dica-vigencia" content="Contrato vigente"><span>ATIVO</span></x-shared.tooltip>');

    // O balão (role="tooltip") precisa de um id e o gatilho precisa referenciá-lo
    // via aria-describedby para que o leitor de tela associe a dica ao controle
    // (WAI-ARIA Tooltip Pattern). Sem isso, o conteúdo nunca chega à AT.
    expect($html)->toContain('role="tooltip"')
        ->and($html)->toContain('id="dica-vigencia"')
        ->and($html)->toContain('aria-describedby="dica-vigencia"');
});

it('gera um id automático e mantém a associação consistente quando id não é informado', function (): void {
    $html = Blade::render('<x-shared.tooltip content="Ajuda contextual"><i class="iconify"></i></x-shared.tooltip>');

    expect($html)->toContain('role="tooltip"');

    // O id gerado para o balão deve ser exatamente o referenciado no aria-describedby.
    preg_match('/id="(tooltip-[A-Za-z0-9]+)"/', $html, $m);
    expect($m)->not->toBeEmpty();

    expect($html)->toContain('aria-describedby="' . $m[1] . '"');
});

it('mantém o conteúdo e o placement (regressão)', function (): void {
    $html = Blade::render('<x-shared.tooltip content="Reemitir boleto" placement="right"><button type="button">x</button></x-shared.tooltip>');

    expect($html)->toContain('role="tooltip"')
        ->and($html)->toContain('Reemitir boleto')
        ->and($html)->toContain('[--placement:right]')
        ->and($html)->toContain('hs-tooltip-toggle');
});
