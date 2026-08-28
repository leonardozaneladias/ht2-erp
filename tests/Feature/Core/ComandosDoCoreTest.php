<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

/**
 * Guarda contra uma regressão que já aconteceu.
 *
 * O Laravel descobre comandos sozinho em app/Console/Commands. Quando os
 * comandos do núcleo foram para ht2ml/core, a descoberta deixou de valer e os
 * cinco sumiram do artisan — sem erro, sem aviso. `access:sync` é passo de
 * deploy; `referencia:sync` também. Sumir em silêncio é o pior modo de falhar.
 */
it('mantém os comandos do core registrados no artisan', function (string $comando) {
    // array_key_exists, e não toHaveKey($chave, $valor): o segundo argumento do
    // toHaveKey é o VALOR esperado, e aqui o valor é o objeto do comando.
    expect(array_key_exists($comando, Artisan::all()))->toBeTrue(
        "O comando {$comando} sumiu do artisan. Se a classe mudou de lugar, "
        . 'declare-a em CoreServiceProvider::registrarComandos().',
    );
})->with([
    'access:sync',
    'access:expirar',
    'referencia:sync',
    'make:modulo',
    'make:recurso',
    'make:regra',
    // Lápide: falha ensinando make:modulo. Some do artisan = some o ensino.
    'make:extensao',
]);

it('não deixa comando do core órfão: todo Command do pacote está registrado', function () {
    $arquivos = glob(base_path('packages/core/src/Console/Commands/*Command.php')) ?: [];

    $declarados = collect($arquivos)
        ->map(fn (string $f): string => 'HT2ML\\Core\\Console\\Commands\\' . basename($f, '.php'))
        ->all();

    $registrados = collect(Artisan::all())->map(fn (object $c): string => $c::class)->values()->all();

    expect(array_diff($declarados, $registrados))->toBe(
        [],
        'Há Command em packages/core/src/Console/Commands que ninguém registra — '
        . 'ele não aparece no artisan. Declare em CoreServiceProvider::registrarComandos().',
    );
});
