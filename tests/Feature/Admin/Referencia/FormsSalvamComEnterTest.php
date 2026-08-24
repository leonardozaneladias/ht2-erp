<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Referencia\FormBanco;
use HT2ML\Core\Livewire\Admin\Referencia\FormCargo;
use HT2ML\Core\Livewire\Admin\Referencia\FormEstado;
use HT2ML\Core\Livewire\Admin\Referencia\FormMoeda;
use HT2ML\Core\Livewire\Admin\Referencia\FormMunicipio;
use HT2ML\Core\Livewire\Admin\Referencia\FormPais;
use HT2ML\Core\Livewire\Admin\Referencia\FormTipoLogradouro;
use HT2ML\Core\Models\AdminUser;
use HT2ML\FiscalBr\Livewire\FormCfop;
use HT2ML\FiscalBr\Livewire\FormCnae;
use HT2ML\FiscalBr\Livewire\FormNcm;
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
});

// PEND-01a: os formulários de referência envolvem o corpo em <form wire:submit="salvar">
// e o botão primário vira type="submit" (sem wire:click). É isso que permite salvar com
// Enter mantendo o clique. O disparo real por Livewire é coberto pelos *CrudTest (->call('salvar')).
it('renderiza o formulário de referência preparado para salvar com Enter', function (string $componente) {
    Livewire::actingAs($this->admin, 'admin')
        ->test($componente)
        ->assertOk()
        ->assertSeeHtml('wire:submit="salvar"')
        ->assertSeeHtml('type="submit"')
        ->assertDontSeeHtml('wire:click="salvar"');
})->with([
    'banco' => FormBanco::class,
    'cargo' => FormCargo::class,
    'cfop' => FormCfop::class,
    'cnae' => FormCnae::class,
    'estado' => FormEstado::class,
    'moeda' => FormMoeda::class,
    'municipio' => FormMunicipio::class,
    'ncm' => FormNcm::class,
    'pais' => FormPais::class,
    'tipo-logradouro' => FormTipoLogradouro::class,
]);
