<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function criarSuperAdminParaBrowser(): AdminUser
{
    $admin = AdminUser::create([
        'nome' => 'Super Admin Browser',
        'email' => 'browser@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');

    return $admin;
}

it('telas autenticadas carregam sem erros de JavaScript', function () {
    $this->actingAs(criarSuperAdminParaBrowser(), 'admin');

    $paginas = visit([
        '/admin/dashboard',
        '/admin/conta',
        '/admin/conta/notificacoes',
        '/admin/empresas',
        '/admin/empresas/nova',
        '/admin/usuarios',
        '/admin/usuarios/novo',
        '/admin/acesso',
        '/admin/auditoria',
        '/admin/configuracoes',
        '/admin/menus',
        '/admin/comunicados',
    ]);

    $paginas->assertNoJavaScriptErrors();
});

it('telas de autenticação carregam sem erros de JavaScript', function () {
    $paginas = visit([
        '/admin/login',
        '/admin/esqueceu-senha',
    ]);

    $paginas->assertNoJavaScriptErrors();
});
