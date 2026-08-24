<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Actions\Admin\Lgpd\AnonimizarUsuarioAction;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Services\Admin\AuditoriaSeguranca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

it('mascara a PII dos logs antigos do usuário na anonimização', function () {
    $alvo = AdminUser::create([
        'nome' => 'Titular Da Silva',
        'email' => 'titular@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
        'telefone' => '11 91234-5678',
    ]);

    // Fase do titular: ações dele carregam o IP/navegador DELE no contexto.
    $this->app->instance('request', Request::create('/admin', 'GET', server: [
        'REMOTE_ADDR' => '10.9.8.7',
        'HTTP_USER_AGENT' => 'NavegadorDoTitular/1.0',
    ]));
    $this->actingAs($alvo, 'admin');
    $alvo->update(['nome' => 'Titular Editado']);

    // Evento de auth pré-login (sem subject/causer) com o e-mail solto.
    app(AuditoriaSeguranca::class)->loginFalhou('titular@teste.com');

    // Fase do admin: outra request — o IP do ADMIN pode permanecer nos logs.
    $this->app->instance('request', Request::create('/admin', 'GET', server: [
        'REMOTE_ADDR' => '172.16.0.1',
    ]));
    $this->actingAs($this->admin, 'admin');
    app(AnonimizarUsuarioAction::class)->execute($this->admin, $alvo);

    // Nenhuma activity remanescente contém a PII original.
    Activity::query()->get()->each(function (Activity $log): void {
        $serializado = json_encode($log->properties) . json_encode($log->attribute_changes);
        expect($serializado)
            ->not->toContain('titular@teste.com')
            ->not->toContain('Titular Da Silva')
            ->not->toContain('Titular Editado')
            ->not->toContain('11 91234-5678')
            ->not->toContain('10.9.8.7');
    });

    // A contagem fica registrada no evento lgpd.anonimizado.
    $lgpd = Activity::query()->where('log_name', 'lgpd')->where('event', 'anonimizado')->firstOrFail();
    expect($lgpd->getProperty('atividades_mascaradas'))->toBeGreaterThanOrEqual(2);
});

it('não toca em logs de outros titulares nem em diffs de outros models', function () {
    $alvo = criarAdminUser('apagado@teste.com');
    $outro = criarAdminUser('intacto@teste.com');
    $outro->update(['nome' => 'Outro Usuário Vivo']);

    // Diff de um model que NÃO é o usuário (empresa editada pelo alvo):
    // o campo "nome" ali é da empresa — não pode ser mascarado.
    $this->actingAs($alvo, 'admin');
    $empresa = Empresa::create(['nome' => 'Empresa Do Diff', 'ativo' => true]);

    $this->actingAs($this->admin, 'admin');
    app(AnonimizarUsuarioAction::class)->execute($this->admin, $alvo);

    $logOutro = Activity::query()
        ->where('subject_type', AdminUser::class)
        ->where('subject_id', $outro->id)
        ->where('event', 'updated')
        ->firstOrFail();

    $logEmpresa = Activity::query()
        ->where('subject_type', Empresa::class)
        ->where('subject_id', $empresa->id)
        ->where('event', 'created')
        ->firstOrFail();

    expect(data_get($logOutro->attribute_changes, 'attributes.nome'))->toBe('Outro Usuário Vivo')
        ->and(data_get($logEmpresa->attribute_changes, 'attributes.nome'))->toBe('Empresa Do Diff')
        ->and($logEmpresa->getProperty('subject_label'))->toBe('Empresa Do Diff');
});
