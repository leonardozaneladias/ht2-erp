<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Rh\Livewire\Funcionarios\FormFuncionario;
use HT2ML\Rh\Livewire\Funcionarios\IndexFuncionario;
use HT2ML\Rh\Models\Funcionario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

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

    $empresa = HT2ML\Core\Models\Empresa::factory()->create();
    app(HT2ML\Core\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);
});

it('renderiza a listagem de Funcionarios', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexFuncionario::class)
        ->assertOk();
});

it('cria um registro de Funcionario pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormFuncionario::class)
        ->set('nome', 'Exemplo de Nome')
        ->set('cpf', '111.444.777-35')
        ->set('cargo', 'Exemplo de Cargo')
        ->set('salario', 1990)
        ->set('admissao', '2026-01-15')
        ->set('status', 'ativo')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rh.funcionarios.index'));

    expect(Funcionario::query()->count())->toBe(1);
});

it('abre o formulário de edição de Funcionario', function () {
    $registro = Funcionario::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormFuncionario::class, ['funcionario' => $registro->id])
        ->assertOk()
        ->assertSet('funcionarioId', $registro->id);
});
