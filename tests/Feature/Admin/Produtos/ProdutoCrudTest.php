<?php

declare(strict_types=1);

use App\Livewire\Admin\Produtos\FormProduto;
use App\Livewire\Admin\Produtos\IndexProduto;
use App\Models\AdminUser;
use App\Models\Produto;
use Database\Seeders\RolePermissionSeeder;
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

it('renderiza a listagem de Produtos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexProduto::class)
        ->assertOk();
});

it('cria um registro de Produto pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormProduto::class)
        ->set('nome', 'Exemplo de Nome')
        ->set('sku', 'Exemplo de Sku')
        ->set('preco', 1990)
        ->set('descricao', 'Descrição de exemplo gerada pelo teste.')
        ->set('status', 'rascunho')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.produtos.index'));

    expect(Produto::query()->count())->toBe(1);
});

it('abre o formulário de edição de Produto', function () {
    $registro = Produto::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormProduto::class, ['produto' => $registro->id])
        ->assertOk()
        ->assertSet('produtoId', $registro->id);
});
