<?php

declare(strict_types=1);

use App\Livewire\Admin\Exemplos\ExemploTable;
use App\Models\Exemplo;
use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->empresa = Empresa::factory()->create();
    app(TenantContext::class)->definirEmpresa($this->empresa->id);

    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');

    // gestor tem apenas exemplos.listar (sem deletar/restaurar/excluir_permanente).
    $this->gestor = criarAdminUser('gestor@teste.com');
    $this->gestor->assignRole('gestor');
});

it('exclui um registro movendo-o para a lixeira (soft delete)', function () {
    $registro = Exemplo::factory()->create();

    Livewire::actingAs($this->super, 'admin')
        ->test(ExemploTable::class)
        ->call('excluir', $registro->id)
        ->assertHasNoErrors();

    expect(Exemplo::query()->whereKey($registro->id)->exists())->toBeFalse()
        ->and(Exemplo::withTrashed()->find($registro->id)->trashed())->toBeTrue();
});

it('compõe lixeira e escopo: oculta trashed nos ativos e os revela na lixeira', function () {
    Exemplo::factory()->create(['nome' => 'Registro Ativo XYZ']);
    Exemplo::factory()->trashed()->create(['nome' => 'Registro Lixeira ABC']);

    $component = Livewire::actingAs($this->super, 'admin')->test(ExemploTable::class);

    $ativos = $component->instance()->datasource()->pluck('nome')->all();
    expect($ativos)->toContain('Registro Ativo XYZ')
        ->and($ativos)->not->toContain('Registro Lixeira ABC');

    $component->call('alternarLixeira')->assertSet('verLixeira', true);

    $naLixeira = $component->instance()->datasource()->pluck('nome')->all();
    expect($naLixeira)->toContain('Registro Lixeira ABC')
        ->and($naLixeira)->not->toContain('Registro Ativo XYZ');
});

it('restaura um registro da lixeira', function () {
    $registro = Exemplo::factory()->trashed()->create();

    Livewire::actingAs($this->super, 'admin')
        ->test(ExemploTable::class)
        ->call('restaurar', $registro->id)
        ->assertHasNoErrors();

    expect(Exemplo::query()->whereKey($registro->id)->exists())->toBeTrue()
        ->and(Exemplo::withTrashed()->find($registro->id)->trashed())->toBeFalse();
});

it('exclui definitivamente um registro da lixeira (force delete)', function () {
    $registro = Exemplo::factory()->trashed()->create();

    Livewire::actingAs($this->super, 'admin')
        ->test(ExemploTable::class)
        ->call('excluirDefinitivo', $registro->id)
        ->assertHasNoErrors();

    expect(Exemplo::withTrashed()->whereKey($registro->id)->exists())->toBeFalse();
});

it('nega as ações de lixeira a quem não tem permissão (403)', function () {
    $ativo = Exemplo::factory()->create();
    $naLixeira = Exemplo::factory()->trashed()->create();

    Livewire::actingAs($this->gestor, 'admin')->test(ExemploTable::class)
        ->call('excluir', $ativo->id)->assertForbidden();

    Livewire::actingAs($this->gestor, 'admin')->test(ExemploTable::class)
        ->call('restaurar', $naLixeira->id)->assertForbidden();

    Livewire::actingAs($this->gestor, 'admin')->test(ExemploTable::class)
        ->call('excluirDefinitivo', $naLixeira->id)->assertForbidden();
});

it('renderiza as ações de lixeira conforme a permissão', function () {
    $ativo = Exemplo::factory()->create();
    $naLixeira = Exemplo::factory()->trashed()->create();

    // Super-admin vê as ações (controle positivo).
    $this->actingAs($this->super, 'admin');
    $htmlSuperAtivos = view('livewire.admin.exemplos._acoes', ['row' => $ativo, 'verLixeira' => false])->render();
    expect($htmlSuperAtivos)->toContain('solicitarExcluir(' . $ativo->id . ')');

    $htmlSuperLixeira = view('livewire.admin.exemplos._acoes', ['row' => $naLixeira, 'verLixeira' => true])->render();
    expect($htmlSuperLixeira)->toContain('solicitarRestaurar(' . $naLixeira->id . ')')
        ->and($htmlSuperLixeira)->toContain('solicitarExcluirDefinitivo(' . $naLixeira->id . ')');

    // gestor (só listar) não vê nenhuma ação de exclusão/restauração (controle negativo).
    $this->actingAs($this->gestor, 'admin');
    $htmlGestorAtivos = view('livewire.admin.exemplos._acoes', ['row' => $ativo, 'verLixeira' => false])->render();
    expect($htmlGestorAtivos)->not->toContain('solicitarExcluir(');

    $htmlGestorLixeira = view('livewire.admin.exemplos._acoes', ['row' => $naLixeira, 'verLixeira' => true])->render();
    expect($htmlGestorLixeira)->not->toContain('solicitarRestaurar(')
        ->and($htmlGestorLixeira)->not->toContain('solicitarExcluirDefinitivo(');
});

it('respeita o escopo multi-empresa na lixeira', function () {
    $outra = Empresa::factory()->create();

    Exemplo::factory()->trashed()->create(['nome' => 'Trashed Ativa']);

    app(TenantContext::class)->definirEmpresa($outra->id);
    $daOutra = Exemplo::factory()->trashed()->create(['nome' => 'Trashed Outra']);

    app(TenantContext::class)->definirEmpresa($this->empresa->id);

    // Restaurar registro de outra empresa pela empresa ativa → fora do escopo (404).
    expect(fn () => Livewire::actingAs($this->super, 'admin')
        ->test(ExemploTable::class)
        ->call('restaurar', $daOutra->id))
        ->toThrow(ModelNotFoundException::class);

    // A lixeira só revela os registros da empresa ativa.
    $component = Livewire::actingAs($this->super, 'admin')->test(ExemploTable::class);
    $component->call('alternarLixeira');

    $nomes = $component->instance()->datasource()->pluck('nome')->all();
    expect($nomes)->toContain('Trashed Ativa')
        ->and($nomes)->not->toContain('Trashed Outra');
});
