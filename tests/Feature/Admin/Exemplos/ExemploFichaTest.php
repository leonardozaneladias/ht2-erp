<?php

declare(strict_types=1);

use App\Livewire\Admin\Exemplos\IndexExemplo;
use App\Models\Exemplo;
use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Infra do "Ver" (ficha em drawer via ComFicha) — testada 1x aqui, no módulo
// Exemplo (referência viva); os demais módulos cobrem só o happy-path.

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = AdminUser::create([
        'nome' => 'Super Ficha',
        'email' => 'super-ficha-exemplo@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);
    $this->admin->assignRole('super-admin');

    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);
});

it('abre a ficha pelo evento do kebab e mostra os dados formatados', function () {
    $registro = Exemplo::factory()->create(['nome' => 'Poltrona Ficha', 'preco' => 235000]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexExemplo::class)
        ->dispatch('exemplos::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertDispatched('ficha-abrir')
        ->assertSee('Poltrona Ficha')
        ->assertSee('2.350,00');
});

it('registro de outra empresa vira toast de não encontrado (guarda de tenant)', function () {
    $registro = Exemplo::factory()->create();

    $outra = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($outra->id);

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexExemplo::class)
        ->dispatch('exemplos::ver', id: $registro->id)
        ->assertSet('fichaId', null)
        ->assertNotDispatched('ficha-abrir')
        ->assertDispatched('toast');
});

it('abre a ficha de registro na lixeira com badge e sem botão Editar', function () {
    $registro = Exemplo::factory()->create(['nome' => 'Exemplo Lixeirado']);
    $registro->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexExemplo::class)
        ->dispatch('exemplos::ver', id: $registro->id)
        ->assertSet('fichaId', $registro->id)
        ->assertSee('Exemplo Lixeirado')
        ->assertSee('Na lixeira')
        ->assertDontSee(route('admin.exemplos.edit', ['exemplo' => $registro->id]));
});

it('perfil só-consulta vê apenas o item Ver no kebab', function () {
    $registro = Exemplo::factory()->create();

    $consulta = criarAdminUser('so-consulta-exemplo@teste.com');
    concederAcessoDireto($consulta, 'exemplos.listar', TipoConcessao::Grant);

    $this->actingAs($consulta, 'admin');
    $html = view('livewire.admin.exemplos._acoes', ['row' => $registro, 'verLixeira' => false])->render();

    expect($html)->toContain("\$dispatch('exemplos::ver'")
        ->and($html)->not->toContain('Editar')
        ->and($html)->not->toContain('Excluir');

    // No ramo da lixeira o Ver também aparece (decidir restauração).
    $registro->delete();
    $htmlLixeira = view('livewire.admin.exemplos._acoes', ['row' => $registro->fresh(), 'verLixeira' => true])->render();
    expect($htmlLixeira)->toContain("\$dispatch('exemplos::ver'");
});
