<?php

declare(strict_types=1);

use App\Settings\SegurancaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('expõe impersonation_timeout_minutos com default 30', function (): void {
    expect(app(SegurancaSettings::class)->impersonation_timeout_minutos)->toBe(30);
});
