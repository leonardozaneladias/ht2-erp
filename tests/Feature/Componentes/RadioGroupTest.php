<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('liga o hint ao grupo via aria-describedby no fieldset', function (): void {
    $html = Blade::render(
        '<x-shared.radio-group name="genero" label="Gênero" hint="Escolha uma opção" />',
    );

    // Sem isto o <small> do hint tem id órfão e o leitor de tela não o anuncia
    // ao navegar pelos radios (WCAG 1.3.1).
    expect($html)
        ->toContain('aria-describedby="genero-hint"')
        ->toContain('id="genero-hint"');
});

it('liga a mensagem de erro ao grupo via aria-describedby no fieldset', function (): void {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['genero' => 'Selecione um gênero.']));
    View::share('errors', $errors);

    $html = Blade::render('<x-shared.radio-group name="genero" label="Gênero" />');

    // Erro precisa estar associado programaticamente ao campo (WCAG 3.3.1).
    expect($html)
        ->toContain('aria-describedby="genero-error"')
        ->toContain('id="genero-error"')
        ->toContain('Selecione um gênero.');
});

it('combina erro e hint na ordem erro→hint no aria-describedby', function (): void {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['genero' => 'Selecione um gênero.']));
    View::share('errors', $errors);

    $html = Blade::render('<x-shared.radio-group name="genero" hint="Escolha uma opção" />');

    expect($html)->toContain('aria-describedby="genero-error genero-hint"');
});

it('não emite aria-describedby quando não há erro nem hint', function (): void {
    $html = Blade::render('<x-shared.radio-group name="genero" label="Gênero" />');

    // Regressão estrutural: agrupamento nativo via fieldset/legend preservado,
    // sem atributo aria-describedby apontando para id inexistente.
    expect($html)
        ->toContain('<fieldset')
        ->toContain('<legend')
        ->not->toContain('aria-describedby');
});

it('anuncia obrigatoriedade via aria-required no fieldset e indicador no legend', function (): void {
    $html = Blade::render('<x-shared.radio-group name="genero" label="Gênero" required />');

    // aria-required no grupo (fieldset) comunica a obrigatoriedade do conjunto de
    // radios ao leitor de tela; o asterisco no legend é só visual (WCAG 3.3.2).
    expect($html)
        ->toContain('aria-required="true"')
        ->toContain('>*<');
});

it('não emite aria-required quando o grupo não é obrigatório', function (): void {
    $html = Blade::render('<x-shared.radio-group name="genero" label="Gênero" />');

    expect($html)
        ->not->toContain('aria-required')
        ->not->toContain('>*<');
});
