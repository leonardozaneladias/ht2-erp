<?php

declare(strict_types=1);

use App\Actions\Admin\CreateAdminUserAction;
use App\Actions\Admin\Lgpd\AnonimizarUsuarioAction;
use App\Actions\Admin\ToggleAdminUserStatusAction;
use App\Actions\Admin\UpdateAdminUserAction;
use App\DTOs\Admin\AdminUserDTO;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin, 'admin');
});

function logsDoUsuario(AdminUser $usuario, ?string $evento = null): Illuminate\Database\Eloquent\Collection
{
    return Activity::query()
        ->where('subject_type', AdminUser::class)
        ->where('subject_id', $usuario->id)
        ->when($evento !== null, fn ($q) => $q->where('event', $evento))
        ->get();
}

it('cria exatamente um log created com o diff completo e sem duplicidade', function () {
    $usuario = app(CreateAdminUserAction::class)->execute(new AdminUserDTO(
        nome: 'Novo Usuário',
        email: 'novo@teste.com',
        ativo: true,
        roles: ['gestor'],
        password: 'senhaforte',
        telefone: '11 99999-0000',
        cargo: 'Analista',
    ));

    $created = logsDoUsuario($usuario, 'created');
    expect($created)->toHaveCount(1);

    $log = $created->first();
    expect($log->log_name)->toBe('admin_users')
        ->and($log->description)->toBe('Registro criado')
        ->and($log->attribute_changes['attributes']['nome'])->toBe('Novo Usuário')
        ->and($log->attribute_changes['attributes']['email'])->toBe('novo@teste.com')
        ->and($log->attribute_changes['attributes']['telefone'])->toBe('11 99999-0000');

    // Evento de domínio do pivot de roles permanece (não coberto pelo diff).
    expect(logsDoUsuario($usuario, 'perfis_sincronizados'))->toHaveCount(1);
});

it('cria exatamente um log updated com antes/depois ao editar', function () {
    $usuario = criarAdminUser('alvo@teste.com');
    Activity::query()->delete();

    app(UpdateAdminUserAction::class)->execute($usuario, new AdminUserDTO(
        nome: 'Nome Editado',
        email: 'alvo@teste.com',
        ativo: true,
        roles: [],
        telefone: '11 98888-7777',
    ));

    $updated = logsDoUsuario($usuario, 'updated');
    expect($updated)->toHaveCount(1);

    $log = $updated->first();
    expect($log->attribute_changes['old']['nome'])->toBe('Usuário Teste')
        ->and($log->attribute_changes['attributes']['nome'])->toBe('Nome Editado')
        ->and($log->attribute_changes['attributes']['telefone'])->toBe('11 98888-7777');

    // Sem troca de senha nem mudança de roles, não há eventos de domínio extras.
    expect(logsDoUsuario($usuario, 'senha_alterada'))->toHaveCount(0)
        ->and(logsDoUsuario($usuario, 'perfis_sincronizados'))->toHaveCount(0);
});

it('nunca expõe a senha no diff e registra senha_alterada como evento de domínio', function () {
    $usuario = criarAdminUser('senha@teste.com');
    Activity::query()->delete();

    app(UpdateAdminUserAction::class)->execute($usuario, new AdminUserDTO(
        nome: $usuario->nome,
        email: $usuario->email,
        ativo: true,
        roles: [],
        password: 'NovaSenhaForte123',
    ));

    expect(logsDoUsuario($usuario, 'senha_alterada'))->toHaveCount(1);

    Activity::query()->get()->each(function (Activity $log): void {
        $props = json_encode($log->properties) . json_encode($log->attribute_changes);
        expect($props)->not->toContain('NovaSenhaForte123')
            ->and(data_get($log->attribute_changes, 'attributes.password'))->toBeNull()
            ->and(data_get($log->attribute_changes, 'old.password'))->toBeNull();
    });
});

it('captura o toggle de status como diff de ativo', function () {
    $usuario = criarAdminUser('toggle@teste.com');
    Activity::query()->delete();

    app(ToggleAdminUserStatusAction::class)->execute($usuario);

    $updated = logsDoUsuario($usuario, 'updated');
    expect($updated)->toHaveCount(1)
        ->and($updated->first()->attribute_changes['old']['ativo'])->toBeTrue()
        ->and($updated->first()->attribute_changes['attributes']['ativo'])->toBeFalse();
});

it('não loga mudanças apenas em atributos não auditados', function () {
    $usuario = criarAdminUser('ruido@teste.com');
    Activity::query()->delete();

    $usuario->update(['last_login_at' => now(), 'last_login_ip' => '10.0.0.1']);

    expect(Activity::query()->count())->toBe(0);
});

it('captura a exclusão com log deleted', function () {
    $usuario = criarAdminUser('excluido@teste.com');
    Activity::query()->delete();

    $usuario->delete();

    $logs = Activity::query()->where('event', 'deleted')->get();
    expect($logs)->toHaveCount(1)
        ->and($logs->first()->description)->toBe('Registro excluído');
});

it('não vaza PII no log durante a anonimização LGPD', function () {
    $alvo = criarAdminUser('pii@teste.com');
    $alvo->assignRole('gestor');
    Activity::query()->delete();

    app(AnonimizarUsuarioAction::class)->execute($this->admin, $alvo);

    // O log de domínio lgpd existe…
    expect(Activity::query()->where('log_name', 'lgpd')->where('event', 'anonimizado')->count())->toBe(1);

    // …e NENHUM log gravado na anonimização contém a PII original.
    Activity::query()->get()->each(function (Activity $log): void {
        expect(json_encode($log->properties) . json_encode($log->attribute_changes))->not->toContain('pii@teste.com');
    });
});
