<?php

declare(strict_types=1);

use App\Actions\Admin\ConcederAcessoDiretoAction;
use App\DTOs\Admin\ConcessaoAcessoDTO;
use App\Enums\TipoConcessao;
use App\Livewire\Admin\Acesso\ControleAcesso;
use App\Livewire\Admin\Acesso\PainelHistorico;
use Database\Seeders\RolePermissionSeeder;
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

function registrarEventoHistorico(string $motivo = 'evento de teste do historico'): void
{
    $alvo = criarAdminUser('alvo-historico@teste.com');
    $alvo->assignRole('gestor');

    app(ConcederAcessoDiretoAction::class)->execute(
        ConcessaoAcessoDTO::fromArray([
            'adminUserId' => $alvo->id,
            'permissao' => 'usuarios.criar',
            'tipo' => TipoConcessao::Grant,
            'motivo' => $motivo,
        ]),
        test()->admin,
    );
}

it('lista eventos de acesso no painel de histórico', function () {
    registrarEventoHistorico();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelHistorico::class)
        ->assertOk()
        ->assertSee('evento de teste do historico');
});

it('filtra o histórico por tipo de evento', function () {
    registrarEventoHistorico();

    Livewire::actingAs($this->admin, 'admin')
        ->test(PainelHistorico::class)
        ->set('event', 'acesso_revogado')
        ->assertDontSee('evento de teste do historico')
        ->call('limparFiltros')
        ->assertSee('evento de teste do historico');
});

it('o hub abre o histórico para quem tem permissão', function () {
    Livewire::actingAs($this->admin, 'admin')
        ->test(ControleAcesso::class)
        ->call('verHistorico')
        ->assertSet('mostrarHistorico', true)
        ->assertSet('selecionadoId', null);
});

it('o hub bloqueia o histórico para quem não tem acessos.historico', function () {
    $auditorRole = Role::findOrCreate('auditor', 'admin');
    DB::table('roles')->where('id', $auditorRole->id)->update(['nivel' => 20]);
    $auditorRole->givePermissionTo('perfis.listar');

    $auditor = criarAdminUser('auditor@teste.com');
    $auditor->assignRole('auditor');

    Livewire::actingAs($auditor, 'admin')
        ->test(ControleAcesso::class)
        ->call('verHistorico')
        ->assertSet('mostrarHistorico', false);
});
