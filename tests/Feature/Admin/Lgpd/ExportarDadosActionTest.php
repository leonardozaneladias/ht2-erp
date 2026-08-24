<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\Lgpd\ExportarDadosUsuarioAction;
use HT2ML\Core\Services\Admin\Security\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('operador', 10);
});

it('monta perfil/acessos/atividades sem expor o secret do 2FA', function (): void {
    $u = criarAdminUser('u@teste.com');
    $u->assignRole('operador');
    $secret = app(TwoFactorService::class)->gerarSecret();
    $u->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
    activity('test')->performedOn($u->fresh())->log('algo aconteceu');

    $dados = app(ExportarDadosUsuarioAction::class)->execute($u->fresh());

    expect($dados)->toHaveKeys(['perfil', 'acessos', 'atividades'])
        ->and($dados['perfil']['email'])->toBe('u@teste.com')
        ->and($dados['perfil']['dois_fatores_ativo'])->toBeTrue()
        ->and($dados['acessos']['papeis_globais'])->toContain('operador')
        ->and(json_encode($dados))->not->toContain($secret);
});
