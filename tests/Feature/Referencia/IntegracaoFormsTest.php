<?php

declare(strict_types=1);

use App\Livewire\Admin\Empresas\FormEmpresa;
use App\Livewire\Admin\Usuarios\FormUsuario;
use App\Models\Empresa;
use Database\Seeders\Referencia\CargoSeeder;
use Database\Seeders\Referencia\EstadoSeeder;
use Database\Seeders\Referencia\MunicipioSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(EstadoSeeder::class);
    $this->seed(MunicipioSeeder::class);
    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');
});

it('Filial: UF lista 27 estados e Município carrega por UF (server-side)', function () {
    $empresa = Empresa::factory()->create();

    $component = Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $empresa->id]);

    expect($component->instance()->ufsDisponiveis)->toHaveCount(27)
        ->and($component->instance()->municipiosDaFilial)->toBe([]);

    $component->set('filial_estado', 'SP');

    $municipios = $component->instance()->municipiosDaFilial;
    expect($municipios)->toHaveKey('São Paulo')
        ->and(count($municipios))->toBeGreaterThan(500);
});

it('Filial: cidade fora do catálogo permanece selecionável (paridade com cargo)', function () {
    $empresa = Empresa::factory()->create();

    $component = Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $empresa->id])
        ->set('filial_estado', 'SP')
        ->set('filial_cidade', 'Cidade Inventada XYZ');

    expect($component->instance()->municipiosDaFilial)->toHaveKey('Cidade Inventada XYZ');
});

it('Filial: trocar a UF limpa a cidade selecionada', function () {
    $empresa = Empresa::factory()->create();

    $component = Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $empresa->id])
        ->set('filial_estado', 'SP')
        ->set('filial_cidade', 'São Paulo');

    $component->set('filial_estado', 'RJ');

    expect($component->get('filial_cidade'))->toBe('');
});

it('Usuário: o select de cargo lista o catálogo', function () {
    $this->seed(CargoSeeder::class);

    $component = Livewire::actingAs($this->super, 'admin')->test(FormUsuario::class);

    expect($component->instance()->cargosDisponiveis)->toHaveKey('Administrador');
});

it('Usuário: cargo fora do catálogo permanece selecionável ao editar', function () {
    $this->seed(CargoSeeder::class);
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->update(['cargo' => 'Cargo Personalizado XYZ']);

    $component = Livewire::actingAs($this->super, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id]);

    expect($component->instance()->cargosDisponiveis)->toHaveKey('Cargo Personalizado XYZ');
});
