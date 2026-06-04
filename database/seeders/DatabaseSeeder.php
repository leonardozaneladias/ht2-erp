<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
        ]);

        // O ambiente semeado representa um sistema já configurado: pula o Setup Wizard.
        $general = app(\App\Settings\GeneralSettings::class);
        $general->instalado = true;

        if ($general->nome_cliente === '') {
            $general->nome_cliente = 'Empresa Demonstração';
        }

        $general->save();
    }
}
