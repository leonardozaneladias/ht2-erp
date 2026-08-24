<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Services\Admin\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = AdminUser::create([
        'nome' => 'Super',
        'email' => 'super@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');
});

it('exibe o dashboard com as métricas reais', function () {
    actingAs($this->admin, 'admin')
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('Usuários admin')
        ->assertSee('Usuários ativos')
        ->assertSee('Empresas')
        ->assertSee('Eventos hoje');
});

it('DashboardMetrics agrega contagens reais e a série de 6 meses', function () {
    AdminUser::create([
        'nome' => 'Inativo',
        'email' => 'inativo@teste.com',
        'password' => Hash::make('password'),
        'ativo' => false,
    ])->assignRole('gestor');

    Empresa::factory()->count(2)->create();

    $dto = app(DashboardMetrics::class)->obter();

    expect($dto->totalUsuarios)->toBe(2)            // super + inativo
        ->and($dto->usuariosAtivos)->toBe(1)         // apenas o super
        ->and($dto->totalEmpresas)->toBe(2)
        ->and($dto->categorias)->toHaveCount(6)
        ->and($dto->serie)->toHaveCount(6)
        ->and(array_sum($dto->serie))->toBe(2);      // os 2 usuários criados agora
});
