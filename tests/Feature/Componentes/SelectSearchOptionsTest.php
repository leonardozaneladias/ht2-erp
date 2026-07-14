<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/*
 * Regressão do A-16 (doc 31 do RH): options int-keyed (pluck('nome', 'id')) tinham o
 * NOME usado como value — o valor persistido (id) nunca casava com opção alguma e o
 * select renderizava "Selecione" mesmo com o dado salvo (municípios no MeusDados e
 * no form do admin).
 */

it('usa a KEY como value em mapas int-keyed (pluck id => nome)', function () {
    $html = Blade::render(
        '<x-shared.select-search name="municipio" :options="$opcoes" :value="3731" />',
        ['opcoes' => [12 => 'Adamantina', 3731 => 'Presidente Epitácio']],
    );

    expect($html)->toContain('value="3731"')
        ->toContain('Presidente Epitácio')
        ->not->toContain('value="Presidente Epitácio"');
});

it('usa o rótulo como value em listas sequenciais de strings', function () {
    $html = Blade::render(
        '<x-shared.select-search name="status" :options="$opcoes" />',
        ['opcoes' => ['Ativo', 'Inativo']],
    );

    expect($html)->toContain('value="Ativo"')->toContain('value="Inativo"');
});

it('usa a KEY como value em grupos com mapa int-keyed', function () {
    $html = Blade::render(
        '<x-shared.select-search name="g" :options="$opcoes" />',
        ['opcoes' => ['SP' => ['label' => 'São Paulo', 'options' => [3731 => 'Presidente Epitácio']]]],
    );

    expect($html)->toContain('value="3731"')->not->toContain('value="Presidente Epitácio"');
});
