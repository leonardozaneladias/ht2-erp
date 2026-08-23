<?php

declare(strict_types=1);

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('resolve o causer pelo guard admin nos logs do trait Auditavel', function () {
    $admin = criarAdminUser('causer@teste.com');
    $alvo = criarAdminUser('alvo@teste.com');
    $this->actingAs($admin, 'admin');
    Activity::query()->delete();

    $alvo->update(['nome' => 'Nome Novo']);

    $log = Activity::query()->where('event', 'updated')->firstOrFail();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->causer_type)->toBe(AdminUser::class);
});

it('respeita um causer definido explicitamente pela ação', function () {
    $admin = criarAdminUser('logado@teste.com');
    $outro = criarAdminUser('explicito@teste.com');
    $this->actingAs($admin, 'admin');
    Activity::query()->delete();

    activity('test')->causedBy($outro)->log('evento com causer explícito');

    expect(Activity::query()->firstOrFail()->causer_id)->toBe($outro->id);
});

it('grava o subject_label resolvido na escrita', function () {
    $alvo = criarAdminUser('rotulo@teste.com');
    Activity::query()->delete();

    $alvo->update(['nome' => 'Rótulo Humano']);

    $log = Activity::query()->where('event', 'updated')->firstOrFail();
    expect($log->getProperty('subject_label'))->toBe('Rótulo Humano');
});

it('não grava contexto quando a request não tem ip (console/fila)', function () {
    $alvo = criarAdminUser('cli@teste.com');
    Activity::query()->delete();

    // Em console/fila a request capturada do CLI não tem REMOTE_ADDR.
    request()->server->remove('REMOTE_ADDR');

    $alvo->update(['nome' => 'Sem Request']);

    $log = Activity::query()->where('event', 'updated')->firstOrFail();
    expect($log->getProperty('contexto'))->toBeNull();
});

it('grava ip e user_agent quando há request real', function () {
    $alvo = criarAdminUser('http@teste.com');
    Activity::query()->delete();

    $request = Request::create('/admin/usuarios', 'POST', server: [
        'REMOTE_ADDR' => '10.1.2.3',
        'HTTP_USER_AGENT' => 'PestBrowser/1.0',
    ]);
    $this->app->instance('request', $request);

    $alvo->update(['nome' => 'Com Request']);

    $log = Activity::query()->where('event', 'updated')->firstOrFail();
    expect($log->getProperty('contexto'))->toMatchArray([
        'ip' => '10.1.2.3',
        'user_agent' => 'PestBrowser/1.0',
    ]);
});

it('deriva empresa e filial do subject mesmo com outro tenant ativo', function () {
    $empresaA = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $empresaB = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
    $filialB = Filial::create(['empresa_id' => $empresaB->id, 'nome' => 'Filial B1', 'e_matriz' => true, 'ativo' => true]);

    app(TenantContext::class)->definirEmpresa($empresaA->id);
    Activity::query()->delete();

    activity('test')->performedOn($filialB)->log('mexeu na filial de B');

    $log = Activity::query()->firstOrFail();
    expect($log->empresa_id)->toBe($empresaB->id)
        ->and($log->filial_id)->toBe($filialB->id);
});

it('usa o tenant ativo quando o subject não expõe empresa', function () {
    $empresaA = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $alvo = criarAdminUser('semescopo@teste.com');

    app(TenantContext::class)->definirEmpresa($empresaA->id);
    Activity::query()->delete();

    $alvo->update(['nome' => 'Sem Empresa Própria']);

    expect(Activity::query()->firstOrFail()->empresa_id)->toBe($empresaA->id);
});
