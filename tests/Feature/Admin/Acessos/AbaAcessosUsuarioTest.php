<?php

declare(strict_types=1);

use HT2ML\Core\Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Usuarios\FormUsuario;
use HT2ML\Core\Models\PermissionGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
    $this->alvo = criarAdminUser('alvo@teste.com');
    $this->alvo->assignRole('gestor');
});

it('concede acesso extra (grant) pela aba do usuário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $this->alvo->id])
        ->call('abrirFormAcesso')
        ->set('novoTipo', 'grant')
        ->set('novaPermissao', 'usuarios.criar')
        ->set('novoMotivo', 'precisa cadastrar temporariamente')
        ->call('concederAcessoExtra')
        ->assertHasNoErrors();

    expect(PermissionGrant::where('admin_user_id', $this->alvo->id)->where('type', 'grant')->exists())->toBeTrue();
    expect($this->alvo->can('usuarios.criar'))->toBeTrue();
});

it('nega acesso extra (deny) com validade', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $this->alvo->id])
        ->call('abrirFormAcesso')
        ->set('novoTipo', 'deny')
        ->set('novaPermissao', 'usuarios.listar')
        ->set('novaValidade', now()->addDays(7)->format('Y-m-d\TH:i'))
        ->set('novoMotivo', 'suspensão temporária')
        ->call('concederAcessoExtra')
        ->assertHasNoErrors();

    $grant = PermissionGrant::where('admin_user_id', $this->alvo->id)->where('type', 'deny')->firstOrFail();
    expect($grant->expires_at)->not->toBeNull();
    expect($this->alvo->can('usuarios.listar'))->toBeFalse();
});

it('exige motivo e permissão ao conceder', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $this->alvo->id])
        ->call('abrirFormAcesso')
        ->set('novaPermissao', '')
        ->set('novoMotivo', '')
        ->call('concederAcessoExtra')
        ->assertHasErrors(['novaPermissao', 'novoMotivo']);
});

it('revoga acesso extra existente', function () {
    $grant = concederAcessoDireto($this->alvo, 'usuarios.criar', HT2ML\Core\Enums\TipoConcessao::Grant);

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $this->alvo->id])
        ->call('revogarAcessoExtra', $grant->id)
        ->assertHasNoErrors();

    expect($grant->fresh()->revoked_at)->not->toBeNull();
});

it('a aba de acessos não aparece ao criar usuário', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class)
        ->assertDontSee('Acessos específicos');
});
