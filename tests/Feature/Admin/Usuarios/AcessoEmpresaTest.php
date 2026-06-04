<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\FormUsuario;
use App\Models\Empresa;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

it('concede e revoga acesso a empresas pelo formulário', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'B', 'ativo' => true]);
    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('empresasAcesso', [$a->id, $b->id])
        ->call('salvarEmpresas')
        ->assertHasNoErrors();

    expect($alvo->temAcessoAEmpresa($a->id))->toBeTrue()
        ->and($alvo->temAcessoAEmpresa($b->id))->toBeTrue();

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('empresasAcesso', [$a->id])
        ->call('salvarEmpresas');

    expect($alvo->temAcessoAEmpresa($a->id))->toBeTrue()
        ->and($alvo->temAcessoAEmpresa($b->id))->toBeFalse();
});

it('carrega as empresas já acessíveis ao editar', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->assertSet('empresasAcesso', [$a->id]);
});

it('limpa a empresa ativa do usuário se ela deixar de ser acessível', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);
    $alvo->update(['empresa_ativa_id' => $a->id]);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->set('empresasAcesso', [])
        ->call('salvarEmpresas');

    expect($alvo->fresh()->empresa_ativa_id)->toBeNull();
});
