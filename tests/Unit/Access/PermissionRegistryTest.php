<?php

declare(strict_types=1);

use HT2ML\Core\Support\Access\PermissionRegistry;

it('lista os nomes das permissões do catálogo', function () {
    $registry = new PermissionRegistry;

    expect($registry->nomes())
        ->toContain('dashboard.view', 'usuarios.criar', 'perfis.gerenciar', 'acessos.conceder');
});

it('agrupa as permissões por área', function () {
    $registry = new PermissionRegistry;

    $porArea = $registry->porArea();

    expect($porArea->keys()->all())->toContain('dashboard', 'usuarios', 'perfis', 'acessos');
    expect($porArea->get('usuarios'))->not->toBeNull();
});

it('resolve a área de uma permissão conhecida', function () {
    $registry = new PermissionRegistry;

    expect($registry->areaDe('usuarios.criar')?->chave)->toBe('usuarios');
    expect($registry->areaDe('acessos.conceder')?->chave)->toBe('acessos');
});

it('retorna null para a área de uma permissão inexistente', function () {
    $registry = new PermissionRegistry;

    expect($registry->areaDe('inexistente.xpto'))->toBeNull();
});

it('verifica a existência de uma permissão no catálogo', function () {
    $registry = new PermissionRegistry;

    expect($registry->existe('usuarios.criar'))->toBeTrue();
    expect($registry->existe('nao.existe'))->toBeFalse();
});
