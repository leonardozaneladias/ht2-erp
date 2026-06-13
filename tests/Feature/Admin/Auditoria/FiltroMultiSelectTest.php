<?php

declare(strict_types=1);

use App\Livewire\Admin\Auditoria\AuditoriaTable;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function superAdminAuditoria(): AdminUser
{
    $super = criarAdminUser('super-auditoria@teste.com');
    criarRoleAdmin('super-admin', 100);
    $super->assignRole('super-admin');

    return $super;
}

it('filtra a auditoria por múltiplos logs (multi_select log_name)', function (): void {
    activity('canalalpha')->log('registro alpha');
    activity('canalbeta')->log('registro beta');
    activity('canalgama')->log('registro gama');

    $this->actingAs(superAdminAuditoria(), 'admin');

    Livewire::test(AuditoriaTable::class)
        ->set('filters', ['multi_select' => ['log_name' => ['canalalpha', 'canalgama']]])
        ->assertSee('registro alpha')
        ->assertSee('registro gama')
        ->assertDontSee('registro beta');
});

it('multi_select de log vazio mostra todos os registros', function (): void {
    activity('canalalpha')->log('registro alpha');
    activity('canalbeta')->log('registro beta');

    $this->actingAs(superAdminAuditoria(), 'admin');

    Livewire::test(AuditoriaTable::class)
        ->set('filters', ['multi_select' => ['log_name' => []]])
        ->assertSee('registro alpha')
        ->assertSee('registro beta');
});
