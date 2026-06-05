<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('gestor', 50)->givePermissionTo(['usuarios.exportar-dados', 'usuarios.anonimizar']);
    criarRoleAdmin('operador', 10);
});

it('autoriza export e anonimização conforme permissão + hierarquia', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('operador');

    expect(Gate::forUser($gestor)->allows('exportarDados', $alvo))->toBeTrue()
        ->and(Gate::forUser($gestor)->allows('anonimizar', $alvo))->toBeTrue();

    // anonimizar exige hierarquia: gestor não anonimiza outro gestor (mesmo nível)
    $par = criarAdminUser('par@teste.com');
    $par->assignRole('gestor');
    expect(Gate::forUser($gestor)->allows('anonimizar', $par))->toBeFalse();
});
