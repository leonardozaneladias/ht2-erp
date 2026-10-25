<?php

declare(strict_types=1);

namespace HT2ML\ExemploDemo\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Dá ao perfil `gestor` a leitura do módulo Exemplo.
 *
 * Antes esta permissão vivia na lista do RolePermissionSeeder do core — o core
 * sabendo, por literal, o nome de uma permissão desta extensão (ADR-0022). Agora
 * quem concede é quem declara: a extensão registra este seeder no canal do
 * ModuleRegistry, e ele roda depois dos seeders do core, quando o perfil já existe.
 */
final class PermissoesDoGestorSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = Role::query()->where('name', 'gestor')->where('guard_name', 'admin')->first();

        $gestor?->givePermissionTo('exemplos.listar');
    }
}
