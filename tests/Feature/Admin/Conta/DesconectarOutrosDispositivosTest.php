<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Conta\SegurancaConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('exige a senha atual para desconectar outros dispositivos', function () {
    $user = criarAdminUser();

    Livewire::actingAs($user, 'admin')
        ->test(SegurancaConta::class)
        ->set('senhaDesconectar', 'senha-errada')
        ->call('desconectarOutrosDispositivos')
        ->assertHasErrors(['senhaDesconectar']);
});

it('desconecta outros dispositivos com a senha correta e audita', function () {
    $user = criarAdminUser();

    Livewire::actingAs($user, 'admin')
        ->test(SegurancaConta::class)
        ->set('senhaDesconectar', 'password')
        ->call('desconectarOutrosDispositivos')
        ->assertHasNoErrors()
        ->assertSet('senhaDesconectar', '');

    expect(Activity::where('log_name', 'auth')
        ->where('event', 'sessoes-encerradas')
        ->where('causer_id', $user->id)
        ->exists())->toBeTrue();
});

it('a troca da própria senha não derruba a sessão atual (AuthenticateSession)', function () {
    $user = criarAdminUser();
    $this->actingAs($user, 'admin');
    marcarInstalado();

    Livewire::actingAs($user, 'admin')
        ->test(HT2ML\Core\Livewire\Admin\Conta\TrocarSenha::class)
        ->set('senhaAtual', 'password')
        ->set('novaSenha', 'NovaSenhaForte1!')
        ->set('novaSenha_confirmation', 'NovaSenhaForte1!')
        ->call('trocar')
        ->assertHasNoErrors();

    // Próxima request autenticada continua válida (hash da sessão reancorado).
    $this->get(route('admin.dashboard'))->assertOk();
});
