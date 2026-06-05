<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('access:sync');
    criarRoleAdmin('gestor', 50)->givePermissionTo('usuarios.exportar-dados');
    criarRoleAdmin('operador', 10);
});

it('exporta JSON com attachment para quem tem permissão', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->actingAs($gestor, 'admin')
        ->get(route('admin.usuarios.lgpd.json', $alvo))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="dados-usuario-' . $alvo->id . '.json"')
        ->assertJsonPath('perfil.email', 'alvo@teste.com');
});

it('exporta PDF', function (): void {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');
    $alvo = criarAdminUser('alvo@teste.com');

    $resp = $this->actingAs($gestor, 'admin')->get(route('admin.usuarios.lgpd.pdf', $alvo));
    $resp->assertOk();
    expect($resp->headers->get('content-type'))->toContain('application/pdf');
});

it('nega export sem permissão', function (): void {
    $operador = criarAdminUser('op@teste.com');
    $operador->assignRole('operador');
    $alvo = criarAdminUser('alvo@teste.com');

    $this->actingAs($operador, 'admin')
        ->get(route('admin.usuarios.lgpd.json', $alvo))
        ->assertForbidden();
});
