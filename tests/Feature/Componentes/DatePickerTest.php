<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

// Acessibilidade — o ícone de calendário do x-shared.date-picker e do
// x-shared.date-range-picker é puramente decorativo: o nome do campo vem SEMPRE do
// <label for> (e do placeholder), nunca do <i>. Logo recebe aria-hidden="true"
// (mesma convenção de input/button/dropdown-item/kpi-card/file-upload/password-input).
// O date-picker é o componente de data de maior alavancagem da base de formulários
// (datas de nascimento/admissão/vencimento aparecem em dezenas de telas de CRUD).
// Caracteriza também os contratos de associação label↔input, hint/erro
// (aria-describedby) e required, até então sem teste.

it('o ícone de calendário do date-picker recebe aria-hidden', function (): void {
    $html = Blade::render('<x-shared.date-picker name="nascimento" label="Nascimento" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--calendar input-icon"[^>]*aria-hidden="true"/')
        ->toContain('Nascimento') // nome do campo segue no texto do <label>
        ->toContain('for="nascimento"');
});

it('o ícone de calendário do date-range-picker recebe aria-hidden', function (): void {
    $html = Blade::render('<x-shared.date-range-picker name="periodo" label="Período" />');

    expect($html)
        ->toMatch('/<i class="iconify tabler--calendar-event input-icon"[^>]*aria-hidden="true"/')
        ->toContain('Período')
        ->toContain('for="periodo"');
});

it('associa o label ao input pelo id derivado do name (regressão)', function (): void {
    $html = Blade::render('<x-shared.date-picker name="dados.admissao" label="Admissão" />');

    // o fieldId normaliza pontos/colchetes do name para um id de DOM válido
    expect($html)
        ->toContain('for="dados-admissao"')
        ->toContain('id="dados-admissao"')
        ->toContain('name="dados.admissao"');
});

it('marca required com indicador visual e aria-invalid false por padrão (regressão)', function (): void {
    $html = Blade::render('<x-shared.date-picker name="vencimento" label="Vencimento" :required="true" />');

    expect($html)
        ->toContain('required')
        ->toContain('aria-invalid="false"')
        ->toContain('text-danger'); // x-shared.required-indicator (asterisco)
});

it('hint é associado ao input via aria-describedby (regressão)', function (): void {
    $html = Blade::render('<x-shared.date-picker name="evento" label="Evento" hint="Data prevista" />');

    expect($html)
        ->toContain('id="evento-hint"')
        ->toContain('aria-describedby="evento-hint"')
        ->toContain('Data prevista');
});

it('usa placeholder padrão dd/mm/aaaa quando não há enableTime (regressão)', function (): void {
    $html = Blade::render('<x-shared.date-picker name="data" label="Data" />');

    expect($html)->toContain('placeholder="dd/mm/aaaa"');
});
