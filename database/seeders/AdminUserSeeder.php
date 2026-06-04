<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nome' => 'Super Admin',
                'password' => Hash::make('password'),
                'ativo' => true,
            ],
        );
        $admin->assignRole('super-admin');

        $gestor = AdminUser::firstOrCreate(
            ['email' => 'gestor@example.com'],
            [
                'nome' => 'Gestor Demo',
                'password' => Hash::make('password'),
                'ativo' => true,
            ],
        );
        $gestor->assignRole('gestor');

        $this->vincularEmpresaDemo($admin, $gestor);
    }

    private function vincularEmpresaDemo(AdminUser ...$usuarios): void
    {
        $empresa = Empresa::query()->where('nome', 'Empresa Demonstração')->first();

        if ($empresa === null) {
            return;
        }

        foreach ($usuarios as $usuario) {
            $usuario->empresasAcessiveis()->syncWithoutDetaching([
                $empresa->id => ['todas_filiais' => true],
            ]);
            $usuario->update(['empresa_ativa_id' => $empresa->id]);
        }
    }
}
