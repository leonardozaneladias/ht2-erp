<?php

declare(strict_types=1);

use App\Contracts\Referencia\FonteDeCargos;
use App\Contracts\Referencia\FonteDeMunicipios;
use App\Contracts\Referencia\FonteDeUnidadesFederativas;
use App\Livewire\Admin\Empresas\FormEmpresa;
use App\Livewire\Admin\Usuarios\FormUsuario;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| O core funciona sem extensão de localização instalada
|--------------------------------------------------------------------------
|
| Este é o estado do core depois que estados, municípios e cargos saírem para
| ht2ml/localizacao-br: nenhuma fonte ligada no container. Os formulários
| precisam continuar de pé, degradando para texto livre — que é o que essas
| colunas sempre foram no banco (string, sem FK).
|
| Simulado desligando os bindings, que é exatamente o que a ausência do pacote
| produz.
|
*/

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    foreach ([FonteDeUnidadesFederativas::class, FonteDeMunicipios::class, FonteDeCargos::class] as $contrato) {
        app()->forgetInstance($contrato);
        unset(app()[$contrato]);
    }
});

it('o formulário de empresa abre sem catálogo de localidades', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class)
        ->assertOk()
        ->assertSet('filial_estado', '');
});

it('sem fonte ligada, UF e cidade não oferecem opções', function () {
    $componente = Livewire::actingAs($this->admin, 'admin')->test(FormEmpresa::class);

    expect($componente->instance()->ufsDisponiveis())->toBe([])
        ->and($componente->instance()->municipiosDaFilial())->toBe([])
        ->and($componente->instance()->temCatalogoDeLocalidades())->toBeFalse();
});

it('o formulário de usuário abre sem catálogo de cargos', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class)
        ->assertOk();
});

it('com fonte ligada, o select volta a ter opções', function () {
    app()->singleton(FonteDeUnidadesFederativas::class, fn () => new class implements FonteDeUnidadesFederativas
    {
        public function opcoes(): array
        {
            return ['SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro'];
        }
    });

    $componente = Livewire::actingAs($this->admin, 'admin')->test(FormEmpresa::class);

    expect($componente->instance()->temCatalogoDeLocalidades())->toBeTrue()
        ->and($componente->instance()->ufsDisponiveis())->toBe(['SP' => 'São Paulo', 'RJ' => 'Rio de Janeiro']);
});
