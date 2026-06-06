<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renderiza a área da conta com as três abas', function (): void {
    $user = criarAdminUser('conta@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.conta'))
        ->assertOk()
        ->assertSee('Perfil')
        ->assertSee('Segurança')
        ->assertSee('Preferências');
});

it('seleciona a aba via query string', function (): void {
    $user = criarAdminUser('conta@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.conta', ['aba' => 'seguranca']))
        ->assertOk();
});

it('redireciona os placeholders antigos para a conta', function (): void {
    $user = criarAdminUser('conta@teste.com');
    $this->actingAs($user, 'admin');

    $this->get(route('admin.perfil.show'))->assertRedirect(route('admin.conta'));
    $this->get(route('admin.conta.edit'))->assertRedirect(route('admin.conta'));
});
