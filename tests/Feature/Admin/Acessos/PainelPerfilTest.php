<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Acesso\ControleAcesso;
use HT2ML\Core\Livewire\Admin\Acesso\PainelPerfil;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

function perfilEditavel(string $nome = 'editor', int $nivel = 20): Role
{
    $role = Role::findOrCreate($nome, 'admin');
    DB::table('roles')->where('id', $role->id)->update(['nivel' => $nivel]);

    return $role;
}

it('carrega um perfil para edição', function () {
    $perfil = perfilEditavel();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $perfil->id])
        ->assertSet('name', 'editor')
        ->assertSet('podeEditar', true)
        ->assertSee('Permissões')
        ->assertSee('Membros');
});

it('salva as permissões do perfil e avisa o hub', function () {
    $perfil = perfilEditavel();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $perfil->id])
        ->set('permissions', ['dashboard.view', 'usuarios.listar'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('perfil-salvo');

    expect($perfil->fresh()->load('permissions')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['dashboard.view', 'usuarios.listar']);
});

it('detecta alterações não salvas e permite descartar', function () {
    $perfil = perfilEditavel();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $perfil->id])
        ->assertDontSee('não salva')
        ->set('permissions', ['dashboard.view'])
        ->assertSee('não salva')
        ->call('descartar')
        ->assertSet('permissions', [])
        ->assertDontSee('não salva');
});

it('marcarModulo seleciona todas as permissões do módulo', function () {
    $perfil = perfilEditavel();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $perfil->id])
        ->call('marcarModulo', 'usuarios')
        ->assertSet('permissions', fn (array $p): bool => in_array('usuarios.criar', $p, true) && in_array('usuarios.listar', $p, true));
});

it('cria um novo perfil pelo painel', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class)
        ->assertSet('podeEditar', true)
        ->set('name', 'suporte')
        ->set('nivel', 15)
        ->set('permissions', ['dashboard.view'])
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('perfil-salvo');

    expect(Role::where('name', 'suporte')->where('guard_name', 'admin')->exists())->toBeTrue();
});

it('marca um perfil protegido como somente leitura mesmo para super-admin', function () {
    $superId = Role::where('name', 'super-admin')->where('guard_name', 'admin')->value('id');

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $superId])
        ->assertSet('podeEditar', false)
        ->assertSee('somente leitura');
});

it('impede o gestor de editar um perfil acima da sua hierarquia', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    $gestorRole = Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();
    $gestorRole->givePermissionTo('perfis.listar', 'perfis.gerenciar');

    $acima = perfilEditavel('diretor', 60);

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(PainelPerfil::class, ['perfilId' => $acima->id])
        ->assertSet('podeEditar', false);
});

it('hub entra no modo de criação de perfil', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('novoPerfil')
        ->assertSet('modoNovo', true)
        ->assertSet('selecionadoId', null);
});

it('hub seleciona o perfil recém-salvo ao receber o evento', function () {
    $perfil = perfilEditavel('analista', 12);

    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('aoSalvarPerfil', $perfil->id)
        ->assertSet('selecionadoId', $perfil->id)
        ->assertSet('modoNovo', false);
});

it('valida o nome único do perfil ao criar pelo painel', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPerfil::class)
        ->set('name', 'gestor')
        ->set('nivel', 15)
        ->call('salvar')
        ->assertHasErrors('name');
});

it('impede o gestor de criar um perfil de nível igual ou acima do seu', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail()->givePermissionTo('perfis.gerenciar');

    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(PainelPerfil::class)
        ->set('name', 'novo-alto')
        ->set('nivel', 60)
        ->call('salvar');

    expect(Role::where('name', 'novo-alto')->exists())->toBeFalse();
});
