<?php

declare(strict_types=1);

use App\Livewire\Admin\Referencia\FormNcm;
use App\Livewire\Admin\Referencia\IndexNcm;
use App\Livewire\Admin\Referencia\NcmTable;
use App\Models\AdminUser;
use App\Models\Referencia\Ncm;
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
});

it('renderiza a listagem de NCMs', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(IndexNcm::class)
        ->assertOk();
});

it('cria um NCM pelo formulário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormNcm::class)
        ->set('codigo', '99999999')
        ->set('descricao', 'NCM de Teste')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.referencia.ncms.index'));

    expect(Ncm::query()->where('codigo', '99999999')->exists())->toBeTrue();
});

it('edita um NCM existente', function () {
    $ncm = Ncm::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormNcm::class, ['ncm' => $ncm->id])
        ->assertOk()
        ->assertSet('ncmId', $ncm->id)
        ->set('descricao', 'Descrição Atualizada')
        ->call('salvar')
        ->assertHasNoErrors();

    expect($ncm->refresh()->descricao)->toBe('Descrição Atualizada');
});

it('move um NCM para a lixeira e restaura', function () {
    $ncm = Ncm::factory()->create();

    Livewire::actingAs($this->admin, 'admin')
        ->test(NcmTable::class)
        ->call('excluir', $ncm->id);

    expect(Ncm::onlyTrashed()->whereKey($ncm->id)->exists())->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(NcmTable::class)
        ->call('restaurar', $ncm->id);

    expect(Ncm::onlyTrashed()->whereKey($ncm->id)->exists())->toBeFalse()
        ->and(Ncm::whereKey($ncm->id)->exists())->toBeTrue();
});

it('exclui um NCM definitivamente da lixeira', function () {
    $ncm = Ncm::factory()->create();
    $ncm->delete();

    Livewire::actingAs($this->admin, 'admin')
        ->test(NcmTable::class)
        ->call('excluirDefinitivo', $ncm->id);

    expect(Ncm::withTrashed()->whereKey($ncm->id)->exists())->toBeFalse();
});

it('exige permissão para listar NCMs', function () {
    $comum = AdminUser::create([
        'nome' => 'Comum',
        'email' => 'comum@teste.com',
        'password' => Hash::make('password'),
        'ativo' => true,
    ]);

    expect($comum->can('ncms.listar'))->toBeFalse()
        ->and($this->admin->can('ncms.listar'))->toBeTrue();
});
