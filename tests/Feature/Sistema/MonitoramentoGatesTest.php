<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('nega Horizon e Pulse para visitante não autenticado', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse()
        ->and(Gate::allows('viewPulse'))->toBeFalse();
});

it('permite Horizon e Pulse para super-admin (bypass do AccessResolver)', function () {
    $user = criarAdminUser();
    $user->assignRole('super-admin');
    $this->actingAs($user, 'admin');

    expect(Gate::allows('viewHorizon'))->toBeTrue()
        ->and(Gate::allows('viewPulse'))->toBeTrue();
});

it('nega Horizon e Pulse para usuário sem a permissão do módulo sistema', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');
    $this->actingAs($user, 'admin');

    expect(Gate::allows('viewHorizon'))->toBeFalse()
        ->and(Gate::allows('viewPulse'))->toBeFalse();
});

it('permite Horizon via concessão direta de sistema.horizon', function () {
    $user = criarAdminUser();
    concederAcessoDireto($user, 'sistema.horizon', TipoConcessao::Grant);
    $this->actingAs($user, 'admin');

    expect(Gate::allows('viewHorizon'))->toBeTrue()
        ->and(Gate::allows('viewPulse'))->toBeFalse();
});

it('permite Pulse via concessão direta de sistema.pulse', function () {
    $user = criarAdminUser();
    concederAcessoDireto($user, 'sistema.pulse', TipoConcessao::Grant);
    $this->actingAs($user, 'admin');

    expect(Gate::allows('viewPulse'))->toBeTrue()
        ->and(Gate::allows('viewHorizon'))->toBeFalse();
});
