<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Catálogo de permissões: fonte de verdade em config/access.php.
        Artisan::call('access:sync');

        $this->seedRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedRoles(): void
    {
        /** @var array<string, int> $niveis */
        $niveis = config('access.role_levels', []);

        /** @var string $superAdminRole */
        $superAdminRole = config('access.super_admin_role', 'super-admin');

        $superAdmin = Role::findOrCreate($superAdminRole, 'admin');
        DB::table('roles')
            ->where('id', $superAdmin->getKey())
            ->update(['nivel' => $niveis[$superAdminRole] ?? 100]);

        $gestor = Role::findOrCreate('gestor', 'admin');
        DB::table('roles')
            ->where('id', $gestor->getKey())
            ->update(['nivel' => $niveis['gestor'] ?? 50]);
        $gestor->syncPermissions(['dashboard.view', 'usuarios.listar']);
    }
}
