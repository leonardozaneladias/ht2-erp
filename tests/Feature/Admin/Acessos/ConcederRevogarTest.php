<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\ConcederAcessoDiretoAction;
use HT2ML\Core\Actions\Admin\RevogarAcessoDiretoAction;
use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\DTOs\Admin\ConcessaoAcessoDTO;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Models\PermissionGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('concede acesso direto, registra auditoria e reflete em can()', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    app(ConcederAcessoDiretoAction::class)->execute(ConcessaoAcessoDTO::fromArray([
        'adminUserId' => $user->id,
        'permissao' => 'usuarios.criar',
        'tipo' => TipoConcessao::Grant,
        'motivo' => 'acesso temporário ao cadastro',
    ]));

    expect(PermissionGrant::where('admin_user_id', $user->id)->where('type', 'grant')->exists())->toBeTrue();
    expect(Activity::where('event', 'acesso_concedido')->exists())->toBeTrue();
    expect($user->can('usuarios.criar'))->toBeTrue();
});

it('nega acesso direto (deny) e bloqueia a permissão', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    app(ConcederAcessoDiretoAction::class)->execute(ConcessaoAcessoDTO::fromArray([
        'adminUserId' => $user->id,
        'permissao' => 'usuarios.listar',
        'tipo' => TipoConcessao::Deny,
        'motivo' => 'suspensão temporária',
    ]));

    expect($user->can('usuarios.listar'))->toBeFalse();
});

it('upsert lógico não duplica concessão viva do mesmo tipo', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');

    $base = [
        'adminUserId' => $user->id,
        'permissao' => 'usuarios.criar',
        'tipo' => TipoConcessao::Grant,
    ];
    app(ConcederAcessoDiretoAction::class)->execute(ConcessaoAcessoDTO::fromArray([...$base, 'motivo' => 'primeira']));
    app(ConcederAcessoDiretoAction::class)->execute(ConcessaoAcessoDTO::fromArray([...$base, 'motivo' => 'segunda']));

    $permissionId = Permission::where('name', 'usuarios.criar')->where('guard_name', 'admin')->value('id');
    $vivas = PermissionGrant::where('admin_user_id', $user->id)
        ->where('permission_id', $permissionId)
        ->whereNull('revoked_at')
        ->count();

    expect($vivas)->toBe(1);
});

it('recusa criar deny para super-admin', function () {
    $super = criarAdminUser('s@teste.com');
    $super->assignRole('super-admin');

    expect(fn () => app(ConcederAcessoDiretoAction::class)->execute(ConcessaoAcessoDTO::fromArray([
        'adminUserId' => $super->id,
        'permissao' => 'usuarios.listar',
        'tipo' => TipoConcessao::Deny,
        'motivo' => 'x',
    ])))->toThrow(AccessException::class);
});

it('revoga acesso direto, marca revoked_at e audita', function () {
    $user = criarAdminUser();
    $user->assignRole('gestor');
    $grant = concederAcessoDireto($user, 'usuarios.criar', TipoConcessao::Grant);

    app(RevogarAcessoDiretoAction::class)->execute($grant, 'não precisa mais');

    expect($grant->fresh()->revoked_at)->not->toBeNull();
    expect(Activity::where('event', 'acesso_revogado')->exists())->toBeTrue();
    expect($user->can('usuarios.criar'))->toBeFalse();
});
