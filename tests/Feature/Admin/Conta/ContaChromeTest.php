<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('a topbar aponta os itens de perfil/conta para admin.conta', function (): void {
    $user = criarAdminUser('chrome@teste.com');

    $resposta = $this->actingAs($user, 'admin')->get(route('admin.dashboard'))->assertOk();

    $resposta->assertSee(route('admin.conta'), false);
});

it('renderiza o avatar com iniciais quando não há foto', function (): void {
    $user = criarAdminUser('chrome@teste.com'); // nome "Usuário Teste"

    $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('UT'); // iniciais de "Usuário Teste"
});
