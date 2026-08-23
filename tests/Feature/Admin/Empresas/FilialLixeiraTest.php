<?php

declare(strict_types=1);

use App\Livewire\Admin\Empresas\FormEmpresa;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');
    $this->empresa = Empresa::factory()->create();
});

it('move uma filial para a lixeira e a restaura', function () {
    $filial = Filial::factory()->create(['empresa_id' => $this->empresa->id, 'e_matriz' => false]);

    Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('excluirFilial', $filial->id);

    expect(Filial::query()->whereKey($filial->id)->exists())->toBeFalse()
        ->and(Filial::withTrashed()->find($filial->id)->trashed())->toBeTrue();

    Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('restaurarFilial', $filial->id);

    expect(Filial::query()->whereKey($filial->id)->exists())->toBeTrue();
});

it('exclui definitivamente uma filial da lixeira', function () {
    $filial = Filial::factory()->trashed()->create(['empresa_id' => $this->empresa->id, 'e_matriz' => false]);

    Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('excluirDefinitivoFilial', $filial->id);

    expect(Filial::withTrashed()->whereKey($filial->id)->exists())->toBeFalse();
});

it('não exclui a Matriz', function () {
    $matriz = Filial::factory()->create(['empresa_id' => $this->empresa->id, 'e_matriz' => true]);

    Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('excluirFilial', $matriz->id);

    expect(Filial::query()->whereKey($matriz->id)->exists())->toBeTrue();
});

it('a lista de filiais alterna entre ativas e lixeira', function () {
    $ativa = Filial::factory()->create(['empresa_id' => $this->empresa->id, 'nome' => 'Filial Ativa', 'e_matriz' => false]);
    $naLixeira = Filial::factory()->trashed()->create(['empresa_id' => $this->empresa->id, 'nome' => 'Filial Lixeira', 'e_matriz' => false]);

    $component = Livewire::actingAs($this->super, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id]);

    expect($component->instance()->filiais->pluck('id')->all())
        ->toContain($ativa->id)
        ->not->toContain($naLixeira->id);

    $component->call('alternarFiliaisLixeira')->assertSet('verFiliaisLixeira', true);

    expect($component->instance()->filiais->pluck('id')->all())
        ->toContain($naLixeira->id)
        ->not->toContain($ativa->id);
});

it('nega restaurar/force de filial a quem só tem empresas.editar (403)', function () {
    $role = Role::findOrCreate('editor-empresas', 'admin');
    $role->givePermissionTo(Permission::findOrCreate('empresas.listar', 'admin'));
    $role->givePermissionTo(Permission::findOrCreate('empresas.editar', 'admin'));
    $limitado = criarAdminUser('ed@teste.com');
    $limitado->assignRole('editor-empresas');

    $naLixeira = Filial::factory()->trashed()->create(['empresa_id' => $this->empresa->id, 'e_matriz' => false]);

    Livewire::actingAs($limitado, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('restaurarFilial', $naLixeira->id)->assertForbidden();

    Livewire::actingAs($limitado, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('excluirDefinitivoFilial', $naLixeira->id)->assertForbidden();
});
