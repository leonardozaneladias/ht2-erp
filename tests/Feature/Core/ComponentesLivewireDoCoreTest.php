<?php

declare(strict_types=1);

use Livewire\Livewire;

/**
 * Guarda contra uma regressão que já aconteceu.
 *
 * O Livewire encontra componentes sozinho em app/Livewire, derivando o alias do
 * namespace. Quando as telas do admin foram para ht2ml/core, a descoberta
 * deixou de valer e 31 telas passaram a devolver 500 com
 * "Unable to find component" — porque o alias que o blade escreve não existia
 * mais no registro.
 *
 * Este teste falha se um componente do pacote deixar de resolver.
 */
it('registra todo componente Livewire do pacote', function () {
    $raiz = base_path('packages/core/src/Livewire');

    $classes = collect(
        iterator_to_array(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, RecursiveDirectoryIterator::SKIP_DOTS)),
        ),
    )
        ->filter(fn (SplFileInfo $f): bool => $f->getExtension() === 'php')
        ->reject(fn (SplFileInfo $f): bool => str_contains($f->getPathname(), '/Concerns/'))
        ->map(function (SplFileInfo $f) use ($raiz): string {
            $rel = str_replace([$raiz . '/', '.php'], '', $f->getPathname());

            return 'HT2ML\\Core\\Livewire\\' . str_replace('/', '\\', $rel);
        })
        // Traits e classes abstratas não são componentes registráveis.
        ->filter(fn (string $c): bool => class_exists($c) && ! (new ReflectionClass($c))->isAbstract())
        ->filter(fn (string $c): bool => is_subclass_of($c, \Livewire\Component::class))
        ->values();

    $ausentes = $classes->reject(function (string $classe): bool {
        try {
            Livewire::new($classe);

            return true;
        } catch (Throwable) {
            return false;
        }
    })->all();

    expect($ausentes)->toBe(
        [],
        'Componente(s) Livewire do pacote que o Livewire não resolve. Confira o '
        . 'addLocation() em CoreServiceProvider::registrarComponentesLivewire().',
    );
});

it('mantém os aliases que a convenção gerava, para os blades não mudarem', function (string $alias) {
    expect(fn () => Livewire::new($alias))->not->toThrow(Throwable::class);
})->with([
    'admin.usuarios.index-usuarios',
    'admin.auditoria.auditoria-table',
    'admin.acesso.painel-perfil',
    'admin.empresas.form-empresa',
]);
