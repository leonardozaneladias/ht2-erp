<?php

declare(strict_types=1);

namespace Database\Seeders;

use HT2ML\Core\Support\Modules\ModuleRegistry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Dados de referência (reais, via CSV) antes dos seeders de demo.
        $this->call(Referencia\DadosReferenciaSeeder::class);

        // Extensões que semeiam dados consumidos pelos seeders do core
        // (catálogos, tabelas de apoio) registram-se com antesDoCore: true.
        $this->call(ModuleRegistry::seeders(antesDoCore: true));

        $this->call([
            RolePermissionSeeder::class,
            EmpresaSeeder::class,
            AdminUserSeeder::class,
            MenuPadraoSeeder::class,
        ]);

        // Demais extensões: já encontram empresas, perfis e usuários no lugar.
        $this->call(ModuleRegistry::seeders());

        // O ambiente semeado representa um sistema já configurado: pula o Setup Wizard.
        $general = app(\App\Settings\GeneralSettings::class);
        $general->instalado = true;

        if ($general->nome_cliente === '') {
            $general->nome_cliente = 'Empresa Demonstração';
        }

        $general->save();
    }
}
