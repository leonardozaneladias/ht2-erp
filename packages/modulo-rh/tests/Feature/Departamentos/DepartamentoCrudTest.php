<?php

declare(strict_types=1);

use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use HT2ERP\Rh\Livewire\Departamentos\FormDepartamento;
use HT2ERP\Rh\Livewire\Departamentos\IndexDepartamento;
use HT2ERP\Rh\Models\Departamento;
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

    $empresa = App\Models\Empresa::factory()->create();
    app(App\Support\Tenancy\TenantContext::class)->definirEmpresa($empresa->id);
});

it('renderiza a listagem de Departamentos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexDepartamento::class)
        ->assertOk();
});

it('cria um registro de Departamento pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormDepartamento::class)
        ->set('nome', 'Exemplo de Nome')
        ->set('sigla', 'Exemplo de Sigla')
        ->set('status', 'ativo')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rh.departamentos.index'));

    expect(Departamento::query()->count())->toBe(1);
});

it('abre o formulário de edição de Departamento', function () {
    $registro = Departamento::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormDepartamento::class, ['departamento' => $registro->id])
        ->assertOk()
        ->assertSet('departamentoId', $registro->id);
});
