<?php

declare(strict_types=1);

use App\Livewire\Admin\Auditoria\IndexAuditoria;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Actions\Admin\Lgpd\ExpurgarLogsAction;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function criarAdminAuditoria(string $role): AdminUser
{
    $admin = AdminUser::create([
        'nome' => 'Admin ' . fake()->unique()->firstName(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $admin->assignRole($role);

    return $admin;
}

it('renderiza a auditoria para quem tem permissão', function () {
    $super = criarAdminAuditoria('super-admin');

    Livewire::actingAs($super, 'admin')
        ->test(IndexAuditoria::class)
        ->assertOk();
});

it('nega a auditoria para quem não tem auditoria.visualizar', function () {
    $gestor = criarAdminAuditoria('gestor');

    // mount() lança AuthorizationException, que o Livewire converte em 403.
    Livewire::actingAs($gestor, 'admin')
        ->test(IndexAuditoria::class)
        ->assertForbidden();
});

it('isola a auditoria por empresa via scope visiveisPara', function () {
    $gestor = criarAdminAuditoria('gestor');
    $super = criarAdminAuditoria('super-admin');

    $empresaA = Empresa::factory()->create();
    $empresaB = Empresa::factory()->create();

    $tenant = app(TenantContext::class);

    $tenant->definirEmpresa($empresaA->id);
    activity()->log('Evento A');

    $tenant->definirEmpresa($empresaB->id);
    activity()->log('Evento B');

    // Com a empresa A ativa, o gestor (sem auditoria.todas-empresas) só vê A.
    $tenant->definirEmpresa($empresaA->id);
    $visiveisGestor = Activity::query()->visiveisPara($gestor)->pluck('description')->all();

    expect($visiveisGestor)->toContain('Evento A')->not->toContain('Evento B');

    // Super-admin (bypass) enxerga as duas empresas.
    $visiveisSuper = Activity::query()->visiveisPara($super)->pluck('description')->all();

    expect($visiveisSuper)->toContain('Evento A')->toContain('Evento B');
});

it('expurga os logs além do teto de retenção', function () {
    config(['activitylog.clean_after_days' => 365]);

    $super = criarAdminAuditoria('super-admin');
    $this->actingAs($super, 'admin');

    activity()->log('log recente');
    activity()->log('log antigo');
    Activity::query()->where('description', 'log antigo')->update(['created_at' => now()->subDays(400)]);

    app(ExpurgarLogsAction::class)->execute();

    expect(Activity::query()->where('description', 'log antigo')->exists())->toBeFalse()
        ->and(Activity::query()->where('description', 'log recente')->exists())->toBeTrue();
});
