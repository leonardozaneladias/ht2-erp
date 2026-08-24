<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Seeders;

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
        $permissoesGestor = [
            'dashboard.view',
            'usuarios.listar',
            'listagens.multi_empresa',
            // De extensão: existe só se ht2ml/extensao-exemplo-demo estiver
            // instalada. Não é caso especial — o filtro abaixo trata qualquer uma.
            'exemplos.listar',
        ];

        $gestor->syncPermissions($this->somenteExistentes($permissoesGestor));
    }

    /**
     * Descarta o que não está no catálogo de permissões.
     *
     * Antes isto era um `if (config('extensoes.exemplo_demo'))`, que checa um
     * FLAG e não a realidade: no skeleton o flag vem true por default e a
     * extensão não está instalada, então o seed morria com
     * "There is no permission named `exemplos.listar`" — no primeiro
     * migrate --seed de um produto novo.
     *
     * O catálogo é montado pelo ModuleRegistry no boot, com o que cada extensão
     * instalada contribuiu. Filtrar por ele vale para qualquer extensão ausente,
     * hoje e nas próximas.
     *
     * @param  list<string>  $permissoes
     * @return list<string>
     */
    private function somenteExistentes(array $permissoes): array
    {
        /** @var array<string, array<string, mixed>> $modulos */
        $modulos = config('access.modules', []);

        $catalogo = [];
        foreach ($modulos as $doModulo) {
            foreach (array_keys($doModulo) as $nome) {
                $catalogo[$nome] = true;
            }
        }

        return array_values(array_filter(
            $permissoes,
            static fn (string $p): bool => isset($catalogo[$p]),
        ));
    }
}
