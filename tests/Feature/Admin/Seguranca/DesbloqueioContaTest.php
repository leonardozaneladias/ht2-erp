<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\UsuariosTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('super-admin desbloqueia uma conta', function (): void {
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');
    $alvo->forceFill(['bloqueado_ate' => now()->addMinutes(10)])->save();

    $this->actingAs($super, 'admin');

    Livewire::test(UsuariosTable::class)->call('desbloquear', $alvo->id);

    expect($alvo->fresh()->estaBloqueada())->toBeFalse();
});
