<?php

declare(strict_types=1);

use App\Enums\TipoConcessao;
use App\Livewire\Admin\Acesso\ControleAcesso;
use App\Livewire\Admin\Acesso\PainelPessoa;
use App\Models\Empresa;
use App\Models\PermissionGrant;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->empresa = Empresa::create(['nome' => 'Empresa Teste', 'ativo' => true]);
    $this->admin = criarAdminUser('admin@teste.com');
    $this->admin->assignRole('super-admin');

    // Perfis são geridos no escopo da empresa ativa.
    app(TenantContext::class)->definirEmpresa($this->empresa->id);
});

function criarPerfilGerenciavel(string $nome = 'vendas', int $nivel = 20): Role
{
    $role = Role::findOrCreate($nome, 'admin');
    DB::table('roles')->where('id', $role->id)->update(['nivel' => $nivel]);

    return $role;
}

it('carrega a pessoa e mostra as três abas', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->assertSet('podeEditarPerfis', true)
        ->assertSet('podeGerirAcessos', true)
        ->assertSee('pessoa@teste.com')
        ->assertSee('Perfis')
        ->assertSee('Acessos diretos')
        ->assertSee('Acesso efetivo');
});

it('sincroniza os perfis da pessoa com dirty-state', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');
    criarPerfilGerenciavel('vendas', 20);

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->assertDontSee('não salva')
        ->set('roles', ['vendas'])
        ->assertSee('não salva')
        ->call('salvarPerfis')
        ->assertHasNoErrors()
        ->assertDontSee('não salva');

    expect($pessoa->fresh()->rolesNaEmpresa($this->empresa->id)->pluck('name')->all())->toContain('vendas');
});

it('descarta a alteração de perfis', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');
    criarPerfilGerenciavel('vendas', 20);

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->set('roles', ['vendas'])
        ->call('descartarPerfis')
        ->assertSet('roles', []);
});

it('concede e revoga um acesso direto', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');

    $component = Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->set('novoTipo', 'grant')
        ->set('novaPermissao', 'usuarios.criar')
        ->set('novoMotivo', 'acesso temporário de teste')
        ->call('concederAcesso')
        ->assertHasNoErrors();

    expect(PermissionGrant::query()->where('admin_user_id', $pessoa->id)->vigentes()->count())->toBe(1);

    $grant = PermissionGrant::query()->where('admin_user_id', $pessoa->id)->firstOrFail();

    $component->call('revogarAcesso', $grant->id)->assertHasNoErrors();

    expect(PermissionGrant::query()->where('admin_user_id', $pessoa->id)->vigentes()->count())->toBe(0);
});

it('exige motivo ao conceder acesso direto', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->set('novoTipo', 'grant')
        ->set('novaPermissao', 'usuarios.criar')
        ->set('novoMotivo', '')
        ->call('concederAcesso')
        ->assertHasErrors('novoMotivo');
});

it('a aba de acesso efetivo explica a origem de cada permissão', function () {
    $pessoa = criarAdminUser('pessoa@teste.com');
    $pessoa->assignRole('gestor');
    concederAcessoDireto($pessoa, 'usuarios.criar', TipoConcessao::Grant);

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $pessoa->id])
        ->set('aba', 'efetivo')
        ->assertSee('usuarios.listar')
        ->assertSee('Concessão direta')
        ->assertSee('Perfil');
});

it('mostra acessos em leitura quando o ator não pode geri-los', function () {
    DB::table('roles')->where('name', 'gestor')->update(['nivel' => 50]);
    $gestorRole = Role::where('name', 'gestor')->where('guard_name', 'admin')->firstOrFail();
    $gestorRole->givePermissionTo('perfis.listar', 'usuarios.listar');

    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');

    $alvo = criarAdminUser('alvo@teste.com');

    Livewire::actingAs($gestor, 'admin')
        ->test(PainelPessoa::class, ['usuarioId' => $alvo->id])
        ->assertSet('podeEditarPerfis', false)
        ->assertSet('podeGerirAcessos', false);
});

it('bloqueia a lente Pessoas para quem não pode listar usuários', function () {
    $auditorRole = criarPerfilGerenciavel('auditor', 20);
    $auditorRole->givePermissionTo('perfis.listar');

    $auditor = criarAdminUser('auditor@teste.com');
    $auditor->assignRole('auditor');

    Livewire::actingAs($auditor, 'admin')
        ->test(ControleAcesso::class)
        ->call('trocarLente', 'pessoas')
        ->assertSet('lente', 'perfis');
});
