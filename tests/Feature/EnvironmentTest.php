<?php

declare(strict_types=1);

it('boots the application in testing environment', function () {
    expect(app()->environment('testing'))->toBeTrue();
});

it('loads the pt_BR locale', function () {
    expect(config('app.locale'))->toBe('pt_BR');
});

it('uses redis as cache store in non-testing envs (smoke check config)', function () {
    // Em testing o cache default pode ser array, então só checamos que o driver redis está definido.
    expect(config('cache.stores.redis'))->not()->toBeNull();
});
