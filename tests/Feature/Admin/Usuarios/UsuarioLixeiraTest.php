<?php

declare(strict_types=1);

use App\Livewire\Admin\Usuarios\UsuariosTable;
use App\Models\AdminUser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->super = criarAdminUser('super@teste.com');
    $this->super->assignRole('super-admin');
});

it('move um usuário para a lixeira e o restaura', function () {
    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($this->super, 'admin')->test(UsuariosTable::class)
        ->call('excluir', $alvo->id)->assertHasNoErrors();

    expect(AdminUser::query()->whereKey($alvo->id)->exists())->toBeFalse()
        ->and(AdminUser::withTrashed()->find($alvo->id)->trashed())->toBeTrue();

    Livewire::actingAs($this->super, 'admin')->test(UsuariosTable::class)
        ->call('restaurar', $alvo->id)->assertHasNoErrors();

    expect(AdminUser::query()->whereKey($alvo->id)->exists())->toBeTrue();
});

it('o SoftDeletingScope esconde o usuário excluído da autenticação', function () {
    $alvo = criarAdminUser('alvo@teste.com');
    $provider = Auth::guard('admin')->getProvider();

    expect($provider->retrieveByCredentials(['email' => 'alvo@teste.com']))->not->toBeNull();

    $alvo->delete();

    expect($provider->retrieveByCredentials(['email' => 'alvo@teste.com']))->toBeNull();
});

it('proíbe excluir a si mesmo (vale até para super-admin)', function () {
    Livewire::actingAs($this->super, 'admin')->test(UsuariosTable::class)
        ->call('excluir', $this->super->id);

    expect(AdminUser::query()->whereKey($this->super->id)->exists())->toBeTrue();
});

it('respeita a hierarquia: gestor não exclui super-admin', function () {
    $gestorRole = Role::findOrCreate('gestor', 'admin');
    $gestorRole->givePermissionTo(Permission::findOrCreate('usuarios.listar', 'admin'));
    $gestorRole->givePermissionTo(Permission::findOrCreate('usuarios.editar', 'admin'));
    $gestorRole->givePermissionTo(Permission::findOrCreate('usuarios.deletar', 'admin'));
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')->test(UsuariosTable::class)
        ->call('excluir', $this->super->id)->assertForbidden();
});

it('exclui definitivamente um usuário da lixeira', function () {
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->delete();

    Livewire::actingAs($this->super, 'admin')->test(UsuariosTable::class)
        ->call('excluirDefinitivo', $alvo->id)->assertHasNoErrors();

    expect(AdminUser::withTrashed()->whereKey($alvo->id)->exists())->toBeFalse();
});

it('D3: e-mail de usuário na lixeira é liberado e a restauração colidente é bloqueada', function () {
    $original = criarAdminUser('reuso@teste.com');
    $original->delete();

    // O índice unique parcial libera o e-mail do usuário na lixeira para novo cadastro.
    $novo = AdminUser::factory()->create(['email' => 'reuso@teste.com']);
    expect($novo->exists)->toBeTrue();

    // Restaurar o original colidiria com o ativo → bloqueado.
    Livewire::actingAs($this->super, 'admin')->test(UsuariosTable::class)
        ->call('restaurar', $original->id);

    expect(AdminUser::query()->whereKey($original->id)->exists())->toBeFalse();
});

it('nega as ações de lixeira a quem não tem permissão (403)', function () {
    $role = Role::findOrCreate('operador-usuarios', 'admin');
    $role->givePermissionTo(Permission::findOrCreate('usuarios.listar', 'admin'));
    $limitado = criarAdminUser('op@teste.com');
    $limitado->assignRole('operador-usuarios');

    $alvo = criarAdminUser('alvo@teste.com');
    $naLixeira = criarAdminUser('lix@teste.com');
    $naLixeira->delete();

    Livewire::actingAs($limitado, 'admin')->test(UsuariosTable::class)
        ->call('excluir', $alvo->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(UsuariosTable::class)
        ->call('restaurar', $naLixeira->id)->assertForbidden();
    Livewire::actingAs($limitado, 'admin')->test(UsuariosTable::class)
        ->call('excluirDefinitivo', $naLixeira->id)->assertForbidden();
});
