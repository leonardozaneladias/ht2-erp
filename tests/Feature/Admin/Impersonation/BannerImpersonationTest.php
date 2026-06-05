<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renderiza o banner com o nome do alvo e a rota de saída quando personificando', function (): void {
    $original = criarAdminUser('original@teste.com');
    $alvo = AdminUser::create([
        'nome' => 'Maria Alvo',
        'email' => 'maria@teste.com',
        'password' => bcrypt('password'),
        'ativo' => true,
    ]);

    $this->withSession([
        'impersonate.original_id' => $original->id,
        'impersonate.started_at' => time(),
        'impersonate.motivo' => 'suporte ao cliente',
    ])->actingAs($alvo, 'admin');

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Maria Alvo')
        ->assertSee(route('admin.impersonation.sair'));
});

it('não renderiza o banner fora de uma personificação', function (): void {
    $user = criarAdminUser('u@teste.com');

    $this->actingAs($user, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.impersonation.sair'));
});
