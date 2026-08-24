<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Conta\SegurancaConta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('cp@teste.com');
    $this->admin->assignRole('super-admin');
});

it('abre o modal de confirmação ao iniciar uma ação sensível', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacaoDeSenha', 'ativar')
        ->assertSet('confirmandoSenha', true)
        ->assertSet('configurando', false);
});

it('executa a ação após confirmar com a senha correta', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacaoDeSenha', 'ativar')
        ->set('senhaConfirmacao', 'password')
        ->call('confirmarSenha')
        ->assertHasNoErrors()
        ->assertSet('confirmandoSenha', false)
        ->assertSet('configurando', true);
});

it('rejeita a confirmação com senha incorreta', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacaoDeSenha', 'ativar')
        ->set('senhaConfirmacao', 'senha-errada')
        ->call('confirmarSenha')
        ->assertHasErrors(['senhaConfirmacao'])
        ->assertSet('configurando', false);
});

it('executa direto quando a senha foi confirmada recentemente', function () {
    session()->put('auth.password_confirmed_at', time());

    Livewire::actingAs($this->admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('iniciarConfirmacaoDeSenha', 'ativar')
        ->assertSet('confirmandoSenha', false)
        ->assertSet('configurando', true);
});

it('bloqueia a ação sensível chamada diretamente sem confirmação', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(SegurancaConta::class)
        ->call('ativar')
        ->assertForbidden();
});
