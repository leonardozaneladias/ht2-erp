<?php

declare(strict_types=1);

use App\Actions\Admin\Lgpd\AnonimizarUsuarioAction;
use App\Exceptions\AccessException;
use App\Models\Activity;
use App\Services\Admin\Security\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('gestor', 50);
    criarRoleAdmin('operador', 10);
});

it('anonimiza a PII, desfaz vínculos e mantém o log', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');
    $secret = app(TwoFactorService::class)->gerarSecret();
    $alvo->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();
    activity('test')->causedBy($alvo->fresh())->log('agiu');

    app(AnonimizarUsuarioAction::class)->execute($ator, $alvo->fresh());

    $alvo = $alvo->fresh();
    expect($alvo->estaAnonimizado())->toBeTrue()
        ->and($alvo->getAttribute('nome'))->toBe('Usuário anonimizado')
        ->and($alvo->getAttribute('email'))->toBe('anonimizado-' . $alvo->id . '@removido.local')
        ->and((bool) $alvo->getAttribute('ativo'))->toBeFalse()
        ->and($alvo->getAttribute('two_factor_secret'))->toBeNull()
        ->and(Hash::check('password', (string) $alvo->getAttribute('password')))->toBeFalse()
        ->and($alvo->roles)->toHaveCount(0)
        ->and(Activity::query()->where('event', 'anonimizado')->where('causer_id', $ator->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('description', 'agiu')->where('causer_id', $alvo->id)->exists())->toBeTrue();
});

it('recusa self, super-admin, sem hierarquia e já-anonimizado', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');
    $super = criarAdminUser('super@teste.com');
    $super->assignRole('super-admin');
    $par = criarAdminUser('par@teste.com');
    $par->assignRole('gestor');
    $jaAnon = criarAdminUser('anon@teste.com');
    $jaAnon->forceFill(['anonimizado_em' => now()])->save();

    $action = app(AnonimizarUsuarioAction::class);

    expect(fn () => $action->execute($gestor, $gestor))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($gestor, $super))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($gestor, $par))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($gestor, $jaAnon))->toThrow(AccessException::class);
});
