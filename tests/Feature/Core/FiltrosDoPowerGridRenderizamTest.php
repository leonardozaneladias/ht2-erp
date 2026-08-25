<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\FiscalBr\Livewire\CfopTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/*
 * O ViewsDoPowerGridTest prova qual ARQUIVO o finder escolhe. Este prova que ele
 * é de fato usado ao renderizar uma tabela real.
 *
 * Sem os dois, uma mudança na forma como o PowerGrid inclui os filtros passaria
 * despercebida: a tela voltaria ao <select> nativo — perdendo busca dentro do
 * filtro e multi-seleção — sem nenhum teste ficar vermelho.
 */

uses(RefreshDatabase::class);

it('o combobox do núcleo chega ao HTML renderizado de uma tabela real', function (): void {
    $this->seed(RolePermissionSeeder::class);

    $admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole('super-admin');

    // CfopTable tem Filter::multiSelect (opcoesTipo) e Filter::inputText —
    // exercita as duas views sobrescritas que mais importam.
    $html = Livewire::actingAs($admin, 'admin')
        ->test(CfopTable::class)
        ->assertOk()
        ->html();

    expect($html)->toContain('combobox');
});
