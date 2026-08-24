<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Actions\Admin\ConcederAcessoDiretoAction;
use HT2ML\Core\DTOs\Admin\ConcessaoAcessoDTO;
use HT2ML\Core\Enums\TipoConcessao;
use HT2ML\Core\Livewire\Admin\Acesso\ControleAcesso;
use HT2ML\Core\Livewire\Admin\Acesso\PainelHistorico;
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

    Livewire::withoutLazyLoading()->actingAs($this->admin, 'admin')
        ->test(PainelHistorico::class)
        ->assertOk()
        ->assertSee('evento de teste do historico');
});

it('renderiza a barra de paginação com aria-label em PT-BR quando há mais de uma página de eventos', function () {
    // > 10 eventos (perPage) para forçar a barra; a paginação Livewire só a exibe
    // quando hasPages() é true. Guarda a tradução PT-BR (CLAUDE.md §4) do
    // aria-label dos links de página na tela real de histórico de acesso.
    foreach (range(1, 12) as $i) {
        activity()->event('acesso_concedido')->log("evento de acesso {$i}");
    }

    Livewire::withoutLazyLoading()->actingAs($this->admin, 'admin')
        ->test(PainelHistorico::class)
        ->assertOk()
        ->assertSeeHtml('aria-label="Ir para a página')
        ->assertDontSeeHtml('aria-label="Go to page');
});

it('filtra o histórico por tipo de evento', function () {
    registrarEventoHistorico();

    Livewire::withoutLazyLoading()->actingAs($this->admin, 'admin')
        ->test(PainelHistorico::class)
        ->set('event', 'acesso_revogado')
        ->assertDontSee('evento de teste do historico')
        ->call('limparFiltros')
        ->assertSee('evento de teste do historico');
});

it('o hub abre o histórico para quem tem permissão', function () {
    Livewire::withoutLazyLoading()->actingAs($this->admin, 'admin')
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

    Livewire::withoutLazyLoading()->actingAs($auditor, 'admin')
        ->test(ControleAcesso::class)
        ->call('verHistorico')
        ->assertSet('mostrarHistorico', false);
});
