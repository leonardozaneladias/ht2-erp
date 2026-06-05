<?php

declare(strict_types=1);

use App\Livewire\Admin\Impersonation\IniciarImpersonation;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('inicia a personificação a partir do modal e redireciona ao dashboard', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->actingAs($ator, 'admin');
    session(['auth.password_confirmed_at' => time()]); // senha já reconfirmada na janela

    Livewire::test(IniciarImpersonation::class)
        ->call('abrir', $alvo->id)
        ->set('motivo', 'reproduzir problema relatado')
        ->call('confirmarEntrada')
        ->assertRedirect(route('admin.dashboard'));

    expect(app(ImpersonationContext::class)->ativo())->toBeTrue();
});

it('exige motivo com no mínimo 5 caracteres', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    $this->actingAs($ator, 'admin');
    session(['auth.password_confirmed_at' => time()]);

    Livewire::test(IniciarImpersonation::class)
        ->call('abrir', $alvo->id)
        ->set('motivo', 'oi')
        ->call('confirmarEntrada')
        ->assertHasErrors(['motivo']);

    expect(app(ImpersonationContext::class)->ativo())->toBeFalse();
});
