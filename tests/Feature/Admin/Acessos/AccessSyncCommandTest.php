<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('cria as permissões do catálogo com metadados de módulo e label', function () {
    $this->artisan('access:sync')->assertSuccessful();

    $permissao = Permission::query()
        ->where('name', 'usuarios.criar')
        ->where('guard_name', 'admin')
        ->firstOrFail();

    expect($permissao->getAttribute('modulo'))->toBe('usuarios');
    expect($permissao->getAttribute('label'))->toBe('Criar usuários');
    expect($permissao->getAttribute('descricao'))->not->toBeNull();
});

it('é idempotente — rodar duas vezes não duplica permissões', function () {
    $this->artisan('access:sync')->assertSuccessful();
    $total = Permission::query()->where('guard_name', 'admin')->count();

    $this->artisan('access:sync')->assertSuccessful();

    expect(Permission::query()->where('guard_name', 'admin')->count())->toBe($total);
});

it('preenche metadados de uma permissão pré-existente sem metadados', function () {
    Permission::create(['name' => 'usuarios.criar', 'guard_name' => 'admin']);

    $this->artisan('access:sync')->assertSuccessful();

    $permissao = Permission::query()
        ->where('name', 'usuarios.criar')
        ->where('guard_name', 'admin')
        ->firstOrFail();

    expect($permissao->getAttribute('modulo'))->toBe('usuarios');
    expect($permissao->getAttribute('label'))->toBe('Criar usuários');
});

it('com --prune remove permissão órfã que não está em uso', function () {
    $this->artisan('access:sync')->assertSuccessful();
    Permission::create(['name' => 'orfa.teste', 'guard_name' => 'admin']);

    $this->artisan('access:sync --prune')->assertSuccessful();

    expect(Permission::query()->where('name', 'orfa.teste')->exists())->toBeFalse();
});

it('com --prune mantém permissão órfã em uso por uma role', function () {
    $this->artisan('access:sync')->assertSuccessful();
    $orfa = Permission::create(['name' => 'orfa.usada', 'guard_name' => 'admin']);
    Role::findOrCreate('temporaria', 'admin')->givePermissionTo($orfa);

    $this->artisan('access:sync --prune')->assertSuccessful();

    expect(Permission::query()->where('name', 'orfa.usada')->exists())->toBeTrue();
});
