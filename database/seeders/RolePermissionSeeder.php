<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Adicione aqui as permissões do seu domínio
        $permissions = [
            'dashboard.view',
            'usuarios.listar', 'usuarios.criar', 'usuarios.editar', 'usuarios.deletar',
            'perfis.listar', 'perfis.gerenciar',
            'configuracoes.editar',
            'auditoria.visualizar',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findOrCreate('super-admin', 'admin');

        $gestor = Role::findOrCreate('gestor', 'admin');
        $gestor->syncPermissions([
            'dashboard.view',
            'usuarios.listar',
        ]);
    }
}
