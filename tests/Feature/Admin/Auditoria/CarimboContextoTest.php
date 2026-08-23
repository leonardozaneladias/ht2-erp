<?php

declare(strict_types=1);

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa o model custom e resolve a relação empresa do activity_log', function (): void {
    expect(config('activitylog.activity_model'))->toBe(Activity::class);

    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);

    $log = activity('test')->log('evento');
    expect($log)->toBeInstanceOf(Activity::class);

    $log->empresa_id = $empresa->id;
    $log->save();

    $fresh = Activity::findOrFail($log->id);
    expect($fresh->empresa)->not->toBeNull()
        ->and($fresh->empresa->nome)->toBe('Acme');
});

it('carimba empresa_id/filial_id do contexto ativo em toda atividade', function (): void {
    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);
    app(HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);

    activity('test')->log('com contexto');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBe($empresa->id);
});

it('deixa empresa_id nulo quando não há contexto ativo', function (): void {
    app(HT2ML\Core\Support\Tenancy\TenantContext::class)->limpar();

    activity('test')->log('sem contexto');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBeNull();
});

it('preserva empresa_id já setado explicitamente (não sobrescreve)', function (): void {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    app(HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($a->id);

    activity('test')->tap(function (Activity $activity) use ($b): void {
        $activity->empresa_id = $b->id;
    })->log('explícito');

    $log = Activity::latest('id')->firstOrFail();
    expect($log->empresa_id)->toBe($b->id);
});
