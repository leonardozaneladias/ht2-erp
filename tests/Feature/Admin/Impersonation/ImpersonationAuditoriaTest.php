<?php

declare(strict_types=1);

use App\Actions\Admin\Impersonation\IniciarImpersonationAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('operador', 10);
});

it('marca ações feitas durante a personificação com impersonado_por', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigação');

    // Ação durante a personificação: edição do próprio perfil do alvo (LogsActivity).
    $alvo->update(['nome' => 'Nome Alterado']);

    $log = Activity::query()->where('log_name', 'admin_users')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->getProperty('impersonado_por'))->toMatchArray(['id' => $ator->id]);
});

it('não marca o evento de início da personificação', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigação');

    $inicio = Activity::query()->where('log_name', 'impersonation')->where('event', 'iniciada')->first();

    expect($inicio)->not->toBeNull()
        ->and($inicio->causer_id)->toBe($ator->id)
        ->and($inicio->getProperty('motivo'))->toBe('investigação')
        ->and($inicio->getProperty('impersonado_por'))->toBeNull();
});
