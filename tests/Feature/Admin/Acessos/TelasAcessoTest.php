<?php

declare(strict_types=1);

use App\Enums\TipoConcessao;
use App\Livewire\Admin\Acesso\HistoricoAcesso;
use App\Livewire\Admin\Acesso\MatrizPermissoes;
use App\Livewire\Admin\Acesso\SimuladorAcesso;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');
});

it('matriz salva as permissões de um perfil', function () {
    $editor = Role::findOrCreate('editor', 'admin');
    DB::table('roles')->where('id', $editor->id)->update(['nivel' => 20]);

    // A grade é indexada por ID de permissão (nomes têm ponto e quebram wire:model aninhado).
    $dashboardId = Permission::where('name', 'dashboard.view')->where('guard_name', 'admin')->value('id');
    $listarId = Permission::where('name', 'usuarios.listar')->where('guard_name', 'admin')->value('id');

    Livewire::actingAs($this->admin, 'admin')
        ->test(MatrizPermissoes::class)
        ->set("grade.{$editor->id}.{$dashboardId}", true)
        ->set("grade.{$editor->id}.{$listarId}", true)
        ->call('salvarPerfil', $editor->id)
        ->assertHasNoErrors();

    expect($editor->fresh()->load('permissions')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing(['dashboard.view', 'usuarios.listar']);
});

it('matriz não lista a role super-admin como editável', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(MatrizPermissoes::class)
        ->assertViewHas('perfis', fn ($perfis) => $perfis->doesntContain('name', 'super-admin'));
});

it('simulador explica a origem do acesso efetivo', function () {
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('gestor'); // usuarios.listar via role
    concederAcessoDireto($alvo, 'usuarios.criar', TipoConcessao::Grant);
    concederAcessoDireto($alvo, 'configuracoes.editar', TipoConcessao::Deny);

    Livewire::actingAs($this->admin, 'admin')
        ->test(SimuladorAcesso::class)
        ->set('usuarioId', (string) $alvo->id)
        ->assertSee('usuarios.listar')
        ->assertSee('Concessão direta')
        ->assertSee('Negação direta');
});

it('histórico de acesso lista eventos de concessão', function () {
    $alvo = criarAdminUser('alvo@teste.com');
    $alvo->assignRole('gestor');

    app(App\Actions\Admin\ConcederAcessoDiretoAction::class)->execute(
        App\DTOs\Admin\ConcessaoAcessoDTO::fromArray([
            'adminUserId' => $alvo->id,
            'permissao' => 'usuarios.criar',
            'tipo' => TipoConcessao::Grant,
            'motivo' => 'teste de histórico',
        ]),
        $this->admin,
    );

    Livewire::actingAs($this->admin, 'admin')
        ->test(HistoricoAcesso::class)
        ->assertSee('teste de histórico');
});

it('nega acesso às telas para quem não tem permissão', function () {
    $gestor = criarAdminUser('g@teste.com');
    $gestor->assignRole('gestor');

    Livewire::actingAs($gestor, 'admin')
        ->test(MatrizPermissoes::class)
        ->assertForbidden();
});
