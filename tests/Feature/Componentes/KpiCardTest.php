<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — os ícones do KPI card são puramente decorativos: o nome do
// card vem SEMPRE do texto ($label + $value + tendência em texto), nunca dos
// ícones. O ícone principal e o de tendência (trending-up/down/minus) são
// redundantes com o rótulo e com o prefixo +/- da porcentagem, então recebem
// aria-hidden="true" (mesma convenção de button/dropdown-item/unsaved-bar).
// kpi-card é a fundação dos dashboards (core + RH).

it('marca o ícone principal como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-admin.kpi-card label="Usuários" value="42" icon="tabler--users" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--users[^>]*aria-hidden="true"/')
        ->toContain('Usuários')
        ->toContain('42');
});

it('marca o ícone de tendência positiva como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-admin.kpi-card label="Receita" value="R$ 10" icon="tabler--coin" :trend="5.2" />');

    // tendência positiva => trending-up; o sinal +5,2% no texto já transmite a direção.
    expect($html)
        ->toMatch('/<i class="iconify tabler--trending-up[^>]*aria-hidden="true"/')
        ->toContain('+5,2%');
});

it('marca o ícone de tendência negativa como decorativo (aria-hidden)', function (): void {
    $html = Blade::render('<x-admin.kpi-card label="Custos" value="R$ 5" icon="tabler--coin" :trend="-3.1" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--trending-down[^>]*aria-hidden="true"/')
        ->toContain('-3,1%');
});

it('vira <a> quando há href e mantém o nome acessível no texto (regressão)', function (): void {
    $html = Blade::render('<x-admin.kpi-card label="Pedidos" value="7" icon="tabler--shopping-cart" href="/admin/pedidos" />');

    expect($html)
        ->toContain('<a')
        ->toContain('href="/admin/pedidos"')
        ->toContain('Pedidos')
        ->toMatch('/<i class="iconify tabler--shopping-cart[^>]*aria-hidden="true"/');
});

it('sem trend não renderiza o ícone de tendência (regressão)', function (): void {
    $html = Blade::render('<x-admin.kpi-card label="Total" value="99" icon="tabler--box" />');

    expect($html)
        ->not->toContain('tabler--trending-up')
        ->not->toContain('tabler--trending-down')
        ->toContain('99');
});
