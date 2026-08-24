<?php

declare(strict_types=1);

namespace HT2ML\Core\Database\Seeders;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
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
        $empresas = Empresa::query()
            ->whereIn('nome', ['Empresa Demonstração', 'Comércio Demonstração'])
            ->orderByRaw("CASE WHEN nome = 'Empresa Demonstração' THEN 0 ELSE 1 END")
            ->get();

        if ($empresas->isEmpty()) {
            return;
        }

        $vinculos = $empresas->mapWithKeys(
            static fn (Empresa $empresa): array => [$empresa->id => ['todas_filiais' => true]],
        )->all();

        foreach ($usuarios as $usuario) {
            $usuario->empresasAcessiveis()->syncWithoutDetaching($vinculos);
            $usuario->update(['empresa_ativa_id' => $empresas->first()->id]);
        }
    }
}
