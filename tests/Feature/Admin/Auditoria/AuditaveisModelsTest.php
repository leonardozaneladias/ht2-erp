<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\AdminUserConvite;
use App\Models\Empresa;
use App\Models\PermissionGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('audita created e updated de Empresa com diff', function () {
    $empresa = Empresa::create(['nome' => 'Auditada Ltda', 'ativo' => true]);

    $created = Activity::query()->where('log_name', 'empresas')->where('event', 'created')->first();
    expect($created)->not->toBeNull()
        ->and(data_get($created->attribute_changes, 'attributes.nome'))->toBe('Auditada Ltda')
        ->and($created->empresa_id)->toBe($empresa->id);

    $empresa->update(['nome' => 'Renomeada Ltda']);

    $updated = Activity::query()->where('log_name', 'empresas')->where('event', 'updated')->first();
    expect(data_get($updated->attribute_changes, 'old.nome'))->toBe('Auditada Ltda')
        ->and(data_get($updated->attribute_changes, 'attributes.nome'))->toBe('Renomeada Ltda');
});

it('audita Filial carimbando a empresa do subject', function () {
    $empresa = Empresa::create(['nome' => 'Dona da Filial', 'ativo' => true]);
    Activity::query()->delete();

    $filial = $empresa->filiais()->create(['nome' => 'Unidade Norte', 'e_matriz' => false, 'ativo' => true]);

    $log = Activity::query()->where('log_name', 'filiais')->where('event', 'created')->firstOrFail();
    expect($log->empresa_id)->toBe($empresa->id)
        ->and($log->filial_id)->toBe($filial->id)
        ->and($log->getProperty('subject_label'))->toBe('Unidade Norte');
});

it('audita PermissionGrant como verdade crua além do log de domínio', function () {
    $user = criarAdminUser('grant@teste.com');
    $permissao = Permission::findOrCreate('dashboard.view', 'admin');
    Activity::query()->delete();

    PermissionGrant::create([
        'admin_user_id' => $user->id,
        'permission_id' => $permissao->id,
        'type' => 'grant',
        'reason' => 'Teste de auditoria',
    ]);

    expect(Activity::query()->where('log_name', 'permission_grants')->where('event', 'created')->exists())
        ->toBeTrue();
});

it('audita convite sem nunca expor o token_hash', function () {
    $user = criarAdminUser('convidado@teste.com');
    Activity::query()->delete();

    $convite = AdminUserConvite::create([
        'admin_user_id' => $user->id,
        'token_hash' => hash('sha256', 'segredo-do-token'),
        'expira_em' => now()->addDays(7),
    ]);

    $log = Activity::query()->where('log_name', 'admin_user_convites')->where('event', 'created')->firstOrFail();
    $serializado = json_encode($log->attribute_changes) . json_encode($log->properties);

    expect(data_get($log->attribute_changes, 'attributes.admin_user_id'))->toBe($user->id)
        ->and($serializado)->not->toContain(hash('sha256', 'segredo-do-token'));
});
