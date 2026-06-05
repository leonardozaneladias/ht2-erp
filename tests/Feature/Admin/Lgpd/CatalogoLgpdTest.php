<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('publica as permissões LGPD via access:sync', function (): void {
    Artisan::call('access:sync');

    expect(Permission::where('name', 'usuarios.exportar-dados')->where('guard_name', 'admin')->exists())->toBeTrue()
        ->and(Permission::where('name', 'usuarios.anonimizar')->where('guard_name', 'admin')->exists())->toBeTrue();
});
