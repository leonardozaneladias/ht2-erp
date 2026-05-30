<?php

declare(strict_types=1);

use App\Enums\ModuloAcesso;
use App\Support\Access\PermissionRegistry;

it('lista os nomes das permissões do catálogo', function () {
    $registry = new PermissionRegistry;

    expect($registry->nomes())
        ->toContain('dashboard.view', 'usuarios.criar', 'perfis.gerenciar', 'acessos.conceder');
});

it('agrupa as permissões por módulo', function () {
    $registry = new PermissionRegistry;

    $porModulo = $registry->porModulo();

    expect($porModulo->keys()->all())->toContain('dashboard', 'usuarios', 'perfis', 'acessos');
    expect($porModulo->get('usuarios'))->not->toBeNull();
});

it('resolve o módulo de uma permissão conhecida', function () {
    $registry = new PermissionRegistry;

    expect($registry->moduloDe('usuarios.criar'))->toBe(ModuloAcesso::Usuarios);
    expect($registry->moduloDe('acessos.conceder'))->toBe(ModuloAcesso::Acessos);
});

it('retorna null para o módulo de uma permissão inexistente', function () {
    $registry = new PermissionRegistry;

    expect($registry->moduloDe('inexistente.xpto'))->toBeNull();
});

it('verifica a existência de uma permissão no catálogo', function () {
    $registry = new PermissionRegistry;

    expect($registry->existe('usuarios.criar'))->toBeTrue();
    expect($registry->existe('nao.existe'))->toBeFalse();
});
