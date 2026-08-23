<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Models\PermissionGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('marca como revogadas as concessões vencidas e registra auditoria', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    $vencida = concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant, now()->subMinute()->toDateTimeString());
    $vigente = concederAcessoDireto($user, 'usuarios.editar', TipoConcessao::Grant, now()->addDay()->toDateTimeString());

    $this->artisan('access:expirar')->assertSuccessful();

    expect($vencida->fresh()->revoked_at)->not->toBeNull();
    expect($vigente->fresh()->revoked_at)->toBeNull();
    expect(Activity::where('event', 'acessos_expirados')->exists())->toBeTrue();
});

it('não faz nada quando não há concessões vencidas', function () {
    $this->artisan('access:expirar')->assertSuccessful();

    expect(PermissionGrant::query()->whereNotNull('revoked_at')->count())->toBe(0);
    expect(Activity::where('event', 'acessos_expirados')->exists())->toBeFalse();
});

it('o runtime já ignora a concessão vencida antes mesmo do comando rodar', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');
    concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant, now()->subMinute()->toDateTimeString());

    // sem rodar o comando, a permissão já não vale (can() converte null em false)
    expect($user->can('usuarios.criar'))->toBeFalse();
});
