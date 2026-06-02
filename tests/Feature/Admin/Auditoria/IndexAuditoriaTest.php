<?php

declare(strict_types=1);

use App\Actions\Admin\CreateAdminUserAction;
use App\DTOs\Admin\AdminUserDTO;
use App\Livewire\Admin\Auditoria\AuditoriaTable;
use App\Livewire\Admin\Auditoria\IndexAuditoria;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('lista eventos do activity log para super-admin', function () {
    $this->actingAs($this->admin, 'admin');

    app(CreateAdminUserAction::class)->execute(new AdminUserDTO(
        nome: 'Novo Em Auditoria',
        email: 'auditoria@teste.com',
        ativo: true,
        roles: ['gestor'],
        password: 'senhaforte',
    ));

    Livewire::test(AuditoriaTable::class)
        ->assertOk()
        ->assertSee('admin_users')
        ->assertSee('created');
});

it('nega acesso para gestor (sem permissão auditoria.visualizar)', function () {
    $gestor = AdminUser::create([
        'nome' => 'Gestor',
        'email' => 'gestor@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(IndexAuditoria::class)
        ->assertForbidden();
});
