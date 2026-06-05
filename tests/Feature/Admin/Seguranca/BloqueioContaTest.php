<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('estaBloqueada reflete bloqueado_ate', function (): void {
    $user = criarAdminUser('u@teste.com');
    expect($user->estaBloqueada())->toBeFalse();

    $user->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();
    expect($user->fresh()->estaBloqueada())->toBeTrue();

    $user->forceFill(['bloqueado_ate' => now()->subMinute()])->save();
    expect($user->fresh()->estaBloqueada())->toBeFalse();
});
