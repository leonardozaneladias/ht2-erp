<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(
            ['nome' => 'Empresa Demonstração'],
            [
                'razao_social' => 'Empresa Demonstração LTDA',
                'ativo' => true,
            ],
        );

        $empresa->filiais()->firstOrCreate(
            ['nome' => 'Matriz'],
            ['e_matriz' => true, 'ativo' => true],
        );
    }
}
