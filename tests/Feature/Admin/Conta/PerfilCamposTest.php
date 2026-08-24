<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Conta\PerfilConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('salva telefone, cargo e bio no próprio perfil', function () {
    $user = criarAdminUser();

    Livewire::actingAs($user, 'admin')
        ->test(PerfilConta::class)
        ->set('nome', 'Nome Atualizado')
        ->set('telefone', '(11) 98888-7777')
        ->set('cargo', 'Gerente financeiro')
        ->set('bio', 'Responsável pelo financeiro.')
        ->call('salvar')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->nome)->toBe('Nome Atualizado')
        ->and($user->telefone)->toBe('(11) 98888-7777')
        ->and($user->cargo)->toBe('Gerente financeiro')
        ->and($user->bio)->toBe('Responsável pelo financeiro.');
});

it('campos extras são opcionais e podem ser limpos', function () {
    $user = criarAdminUser();
    $user->forceFill(['telefone' => '11999998888', 'cargo' => 'Analista', 'bio' => 'x'])->save();

    Livewire::actingAs($user, 'admin')
        ->test(PerfilConta::class)
        ->set('telefone', '')
        ->set('cargo', '')
        ->set('bio', '')
        ->call('salvar')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->telefone)->toBeNull()
        ->and($user->cargo)->toBeNull()
        ->and($user->bio)->toBeNull();
});

it('valida o limite da bio', function () {
    $user = criarAdminUser();

    Livewire::actingAs($user, 'admin')
        ->test(PerfilConta::class)
        ->set('bio', str_repeat('a', 501))
        ->call('salvar')
        ->assertHasErrors(['bio']);
});
