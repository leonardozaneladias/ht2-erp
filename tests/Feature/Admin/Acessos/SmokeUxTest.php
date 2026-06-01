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

it('renderiza todas as telas do módulo de acesso sem erro (HTTP 200)', function (string $rota) {
    $this->actingAs($this->admin, 'admin')
        ->get(route($rota))
        ->assertOk();
})->with([
    'admin.dashboard',
    'admin.usuarios.index',
    'admin.usuarios.create',
    'admin.perfis.index',
    'admin.perfis.create',
    'admin.acesso.matriz',
    'admin.acesso.simulador',
    'admin.acesso.historico',
    'admin.auditoria.index',
]);

it('renderiza a edição de perfil e de usuário sem erro', function () {
    $perfil = Spatie\Permission\Models\Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();

    $this->actingAs($this->admin, 'admin')->get(route('admin.perfis.edit', $perfil))->assertOk();
    $this->actingAs($this->admin, 'admin')->get(route('admin.usuarios.edit', $this->admin))->assertOk();
});

it('persiste o guard admin nas requisições de update do Livewire', function () {
    // Sem este middleware persistente, $this->authorize() em métodos de ação
    // resolveria o guard padrão (web, vazio) e retornaria 403 no navegador.
    expect(Livewire\Livewire::getPersistentMiddleware())
        ->toContain(App\Http\Middleware\AdminAuthenticate::class);
});
