<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Actions\Admin\AtualizarFilialAction;
use HT2ML\Core\DTOs\Admin\FilialDTO;
use HT2ML\Core\Exceptions\FilialException;
use HT2ML\Core\Livewire\Admin\Empresas\FormEmpresa;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Models\Filial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = criarAdminUser('super@teste.com');
    $this->admin->assignRole('super-admin');

    $this->empresa = Empresa::factory()->create();
    $this->empresa->filiais()->create(['nome' => 'Matriz', 'e_matriz' => true, 'ativo' => true]);
});

it('cria filial pela tela de edição da empresa e grava auditoria', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('abrirFormFilial')
        ->set('filial_nome', 'Unidade Campinas')
        ->set('filial_cidade', 'Campinas')
        ->set('filial_estado', 'SP')
        ->call('salvarFilial')
        ->assertHasNoErrors()
        ->assertSet('mostrarFormFilial', false);

    $filial = Filial::where('nome', 'Unidade Campinas')->firstOrFail();

    // Auditoria automática: created da filial via trait Auditavel, com a
    // empresa carimbada a partir do subject.
    $log = Activity::where('log_name', 'filiais')
        ->where('event', 'created')
        ->where('subject_id', $filial->id)
        ->first();

    expect($filial->empresa_id)->toBe($this->empresa->id)
        ->and($filial->e_matriz)->toBeFalse()
        ->and($log)->not->toBeNull()
        ->and($log->empresa_id)->toBe($this->empresa->id);
});

it('edita filial existente', function () {
    $filial = $this->empresa->filiais()->create(['nome' => 'Unidade A', 'e_matriz' => false, 'ativo' => true]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('abrirFormFilial', $filial->id)
        ->assertSet('filial_nome', 'Unidade A')
        ->set('filial_nome', 'Unidade B')
        ->set('filial_ativo', false)
        ->call('salvarFilial')
        ->assertHasNoErrors();

    $filial->refresh();

    expect($filial->nome)->toBe('Unidade B')
        ->and($filial->ativo)->toBeFalse();
});

it('não permite desativar a Matriz', function () {
    $matriz = $this->empresa->filiais()->where('e_matriz', true)->firstOrFail();

    expect(fn () => app(AtualizarFilialAction::class)->execute(
        $matriz,
        FilialDTO::fromArray(['nome' => 'Matriz', 'ativo' => false]),
    ))->toThrow(FilialException::class);

    expect($matriz->fresh()->ativo)->toBeTrue();
});

it('lista a Matriz primeiro e exige permissão de edição', function () {
    $this->empresa->filiais()->create(['nome' => 'AAA Unidade', 'e_matriz' => false, 'ativo' => true]);

    $componente = Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id]);

    expect($componente->instance()->filiais->first()->e_matriz)->toBeTrue();

    // Usuário sem empresas.editar não acessa o form de edição.
    $semAcesso = criarAdminUser('comum@teste.com');

    Livewire::actingAs($semAcesso, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->assertForbidden();
});

it('valida campos da filial', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormEmpresa::class, ['empresa' => $this->empresa->id])
        ->call('abrirFormFilial')
        ->set('filial_nome', 'X')
        ->set('filial_estado', 'ABC')
        ->call('salvarFilial')
        ->assertHasErrors(['filial_nome', 'filial_estado']);
});
