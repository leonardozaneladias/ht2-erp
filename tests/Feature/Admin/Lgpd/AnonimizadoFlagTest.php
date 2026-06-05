<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('estaAnonimizado reflete anonimizado_em', function (): void {
    $u = criarAdminUser('u@teste.com');
    expect($u->estaAnonimizado())->toBeFalse();

    $u->forceFill(['anonimizado_em' => now()])->save();
    expect($u->fresh()->estaAnonimizado())->toBeTrue();
});
