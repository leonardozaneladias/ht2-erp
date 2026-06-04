<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'smoke@teste.com',
        'password' => bcrypt('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('renderiza as telas do módulo de acesso sem erro (HTTP 200)', function (string $rota) {
    $this->actingAs($this->admin, 'admin')
        ->get(route($rota))
        ->assertOk();
})->with([
    'admin.dashboard',
    'admin.usuarios.index',
    'admin.usuarios.create',
    'admin.acesso.index',
    'admin.auditoria.index',
]);

it('redireciona as rotas legadas de acesso para o hub', function (string $rota) {
    $this->actingAs($this->admin, 'admin')
        ->get(route($rota))
        ->assertRedirect(route('admin.acesso.index'));
})->with([
    'admin.perfis.index',
    'admin.perfis.create',
    'admin.acesso.matriz',
    'admin.acesso.simulador',
    'admin.acesso.historico',
]);

it('renderiza a edição de usuário sem erro', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.usuarios.edit', $this->admin))
        ->assertOk();
});

it('persiste o guard admin nas requisições de update do Livewire', function () {
    // Sem este middleware persistente, $this->authorize() em métodos de ação
    // resolveria o guard padrão (web, vazio) e retornaria 403 no navegador.
    expect(Livewire\Livewire::getPersistentMiddleware())
        ->toContain(App\Http\Middleware\AdminAuthenticate::class);
});
