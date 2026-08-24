<?php

declare(strict_types=1);

use HT2ML\Core\Listeners\RegistrarLoginAdmin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

/**
 * Guarda contra uma regressão que já aconteceu.
 *
 * O Laravel descobre listeners sozinho em app/Listeners. Quando
 * RegistrarLoginAdmin foi para ht2ml/core, a descoberta deixou de valer e o
 * histórico de login parou de ser gravado — sem erro, sem aviso. Um relatório
 * de segurança ficaria vazio sem ninguém perceber.
 */
it('mantém o listener de login do core registrado', function () {
    $ouvintes = Event::getRawListeners()[Login::class] ?? [];

    // in_array, e não toContain($valor, $msg): o segundo argumento do toContain
    // é outro VALOR esperado, não a mensagem de falha.
    expect(in_array(RegistrarLoginAdmin::class, $ouvintes, true))->toBeTrue(
        'O listener de login do core não está registrado. Declare em '
        . 'CoreServiceProvider::registrarListeners().',
    );
});

it('não deixa listener do core órfão: todo Listener do pacote está registrado', function () {
    $arquivos = glob(base_path('packages/core/src/Listeners/*.php')) ?: [];

    $declarados = collect($arquivos)
        ->map(fn (string $f): string => 'HT2ML\\Core\\Listeners\\' . basename($f, '.php'))
        ->all();

    $registrados = collect(Event::getRawListeners())
        ->flatten()
        ->filter(fn (mixed $l): bool => is_string($l))
        ->map(fn (string $l): string => explode('@', $l)[0])
        ->all();

    expect(array_diff($declarados, $registrados))->toBe(
        [],
        'Há Listener em packages/core/src/Listeners que ninguém registra — '
        . 'ele nunca é chamado. Declare em CoreServiceProvider::registrarListeners().',
    );
});
