<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
});

it('permite personificar alvo de nível inferior quando tem a permissão', function (): void {
    $gestorRole = criarRoleAdmin('gestor', 50);
    $gestorRole->givePermissionTo('usuarios.impersonar');
    criarRoleAdmin('operador', 10);

    $ator = criarAdminUser('ator@teste.com');
    $ator->assignRole('gestor');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    expect(Gate::forUser($ator)->allows('impersonate', $alvo))->toBeTrue();
});

it('nega sem a permissão, ou contra nível igual/superior', function (): void {
    criarRoleAdmin('gestor', 50);
    criarRoleAdmin('operador', 10);

    $semPermissao = criarAdminUser('sem@teste.com');
    $semPermissao->assignRole('gestor'); // sem usuarios.impersonar
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    expect(Gate::forUser($semPermissao)->allows('impersonate', $alvo))->toBeFalse();

    // mesmo com a permissão, não personifica um par (nível igual)
    $gestorRole = criarRoleAdmin('gestor', 50)->givePermissionTo('usuarios.impersonar');
    $par = criarAdminUser('par@teste.com');
    $par->assignRole('gestor');

    expect(Gate::forUser($semPermissao)->allows('impersonate', $par))->toBeFalse();
});
