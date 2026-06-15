<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $demonstracao = Empresa::firstOrCreate(
            ['nome' => 'Empresa Demonstração'],
            [
                'razao_social' => 'Empresa Demonstração LTDA',
                'ativo' => true,
            ],
        );

        $demonstracao->filiais()->firstOrCreate(
            ['nome' => 'Matriz'],
            ['e_matriz' => true, 'ativo' => true],
        );

        // Segunda filial: demonstra a dimensão filial do filtro multi-empresa.
        $demonstracao->filiais()->firstOrCreate(
            ['nome' => 'Filial Centro'],
            ['e_matriz' => false, 'ativo' => true],
        );

        // Segunda empresa: habilita o filtro multi-empresa nas listagens (exige
        // 2+ empresas elegíveis) para usuários com a capacidade.
        $comercio = Empresa::firstOrCreate(
            ['nome' => 'Comércio Demonstração'],
            [
                'razao_social' => 'Comércio Demonstração LTDA',
                'ativo' => true,
            ],
        );

        $comercio->filiais()->firstOrCreate(
            ['nome' => 'Matriz'],
            ['e_matriz' => true, 'ativo' => true],
        );
    }
}
