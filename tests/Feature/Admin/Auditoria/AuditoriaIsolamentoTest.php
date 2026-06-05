<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('publica a permissão auditoria.todas-empresas via access:sync', function (): void {
    Artisan::call('access:sync');

    expect(Permission::where('name', 'auditoria.todas-empresas')->where('guard_name', 'admin')->exists())
        ->toBeTrue();
});
