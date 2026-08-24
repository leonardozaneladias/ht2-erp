<?php

declare(strict_types=1);

use HT2ML\Core\Livewire\Admin\Lgpd\AnonimizarUsuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('anonimiza pelo modal com confirmação + senha', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->actingAs($ator, 'admin');
    session(['auth.password_confirmed_at' => time()]);

    Livewire::test(AnonimizarUsuario::class)
        ->call('abrir', $alvo->id)
        ->set('confirmacao', 'ANONIMIZAR')
        ->call('confirmar');

    expect($alvo->fresh()->estaAnonimizado())->toBeTrue();
});

it('exige a palavra exata de confirmação', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->actingAs($ator, 'admin');
    session(['auth.password_confirmed_at' => time()]);

    Livewire::test(AnonimizarUsuario::class)
        ->call('abrir', $alvo->id)
        ->set('confirmacao', 'errado')
        ->call('confirmar')
        ->assertHasErrors('confirmacao');

    expect($alvo->fresh()->estaAnonimizado())->toBeFalse();
});
