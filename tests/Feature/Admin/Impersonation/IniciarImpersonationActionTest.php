<?php

declare(strict_types=1);

use HT2ML\Core\Actions\Admin\Impersonation\IniciarImpersonationAction;
use HT2ML\Core\Exceptions\AccessException;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Impersonation\ImpersonationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('super-admin', 100);
    criarRoleAdmin('gestor', 50)->givePermissionTo('usuarios.impersonar');
    criarRoleAdmin('operador', 10);
});

it('super-admin personifica: troca o usuário autenticado e ativa o contexto', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);

    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'investigar bug do cliente');

    expect(Auth::guard('admin')->id())->toBe($alvo->id)
        ->and(app(ImpersonationContext::class)->ativo())->toBeTrue()
        ->and(app(ImpersonationContext::class)->originalId())->toBe($ator->id);
});

it('recusa auto-personificação, alvo inativo, super-admin e hierarquia', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $superAlvo = criarAdminUser('super@teste.com');
    $superAlvo->assignRole('super-admin');
    $inativo = criarAdminUser('inativo@teste.com', ativo: false);
    $inativo->assignRole('operador');

    $action = app(IniciarImpersonationAction::class);

    expect(fn () => $action->execute($ator, $ator, 'x'))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($ator, $inativo, 'x'))->toThrow(AccessException::class)
        ->and(fn () => $action->execute($ator, $superAlvo, 'x'))->toThrow(AccessException::class);
});

it('gestor só personifica alvo da empresa ativa dele', function (): void {
    $empresa = Empresa::create(['nome' => 'Acme', 'ativo' => true]);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $ator->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);
    $ator->update(['empresa_ativa_id' => $empresa->id]);
    $ator = $ator->fresh(); // simula carregamento do banco (empresa_ativa_id pode vir como string)

    $alvoMesmaEmpresa = criarAdminUser('mesma@teste.com');
    $alvoMesmaEmpresa->assignRole('operador');
    $alvoMesmaEmpresa->empresasAcessiveis()->attach($empresa->id, ['todas_filiais' => true]);

    $alvoForaDaEmpresa = criarAdminUser('fora@teste.com');
    $alvoForaDaEmpresa->assignRole('operador');

    $action = app(IniciarImpersonationAction::class);

    expect(fn () => $action->execute($ator, $alvoForaDaEmpresa, 'x'))->toThrow(AccessException::class);

    $action->execute($ator, $alvoMesmaEmpresa, 'suporte na empresa');
    expect(app(ImpersonationContext::class)->ativo())->toBeTrue();
});

it('recusa personificar um par (mesmo nível hierárquico)', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $par = criarAdminUser('par@teste.com');
    $par->assignRole('gestor');

    expect(fn () => app(IniciarImpersonationAction::class)->execute($ator, $par, 'tentativa'))
        ->toThrow(AccessException::class);
});

it('recusa iniciar uma personificação aninhada', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');
    $outro = criarAdminUser('outro@teste.com');
    $outro->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'primeira personificação');

    // já personificando: nova chamada deve ser recusada
    expect(fn () => app(IniciarImpersonationAction::class)->execute($alvo, $outro, 'aninhada'))
        ->toThrow(AccessException::class);
});

it('registra o evento de auditoria de início com o ator como causer', function (): void {
    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('super-admin');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    Auth::guard('admin')->login($ator);
    app(IniciarImpersonationAction::class)->execute($ator, $alvo, 'auditoria');

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'impersonation',
        'event' => 'iniciada',
        'causer_id' => $ator->id,
    ]);
});
