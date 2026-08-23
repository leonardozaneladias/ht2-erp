<?php

declare(strict_types=1);

use HT2ML\Core\Models\LoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('persiste locale/timezone e relaciona o histórico de logins', function (): void {
    $user = criarAdminUser('dados@teste.com');
    $user->forceFill(['locale' => 'en', 'timezone' => 'UTC'])->save();

    LoginHistory::create([
        'admin_user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'PHPUnit',
    ]);

    $fresh = $user->fresh();

    expect($fresh->locale)->toBe('en')
        ->and($fresh->timezone)->toBe('UTC')
        ->and($fresh->loginHistory()->count())->toBe(1)
        ->and($fresh->loginHistory()->first()->ip_address)->toBe('203.0.113.7');
});

it('urlAvatar retorna null sem avatar e a URL pública quando há caminho', function (): void {
    Storage::fake('public');
    $user = criarAdminUser('avatar@teste.com');

    expect($user->urlAvatar())->toBeNull();

    $user->forceFill(['avatar_url' => 'avatars/foto.jpg'])->save();

    expect($user->fresh()->urlAvatar())->toBe(Storage::disk('public')->url('avatars/foto.jpg'));
});
