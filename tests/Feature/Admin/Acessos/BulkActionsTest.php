<?php

declare(strict_types=1);

use App\Actions\Admin\AtribuirPerfilEmMassaAction;
use App\Actions\Admin\BulkUserStatusAction;
use App\Actions\Admin\DefinirPerfilAtivoAction;
use App\DTOs\Admin\AtribuicaoPerfilMassaDTO;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('atribui perfil a vários usuários de uma vez', function () {
    $a = criarAdminUser('a@teste.com');
    $b = criarAdminUser('b@teste.com');

    $afetados = app(AtribuirPerfilEmMassaAction::class)->execute(
        AtribuicaoPerfilMassaDTO::fromArray(['adminUserIds' => [$a->id, $b->id], 'roles' => ['gestor']]),
    );

    expect($afetados)->toBe(2);
    expect($a->fresh(['roles'])->getRoleNames()->all())->toContain('gestor');
    expect($b->fresh(['roles'])->getRoleNames()->all())->toContain('gestor');
});

it('desativa usuários em massa, ignorando o próprio ator', function () {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $this->actingAs($ator, 'admin');

    $a = criarAdminUser('a@teste.com');
    $b = criarAdminUser('b@teste.com');

    $afetados = app(BulkUserStatusAction::class)->execute([$a->id, $b->id, $ator->id], false);

    expect($afetados)->toBe(2);
    expect($a->fresh()->ativo)->toBeFalse();
    expect($ator->fresh()->ativo)->toBeTrue();
});

it('define e limpa o perfil ativo', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');
    $gestor = Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();

    $resultado = app(DefinirPerfilAtivoAction::class)->execute($user, $gestor->id);
    expect($resultado->perfil_ativo_role_id)->toBe($gestor->id);
    expect(session('admin.perfil_ativo_role_id'))->toBe($gestor->id);

    $resultado = app(DefinirPerfilAtivoAction::class)->execute($user->fresh(), null);
    expect($resultado->perfil_ativo_role_id)->toBeNull();
});
