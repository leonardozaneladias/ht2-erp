<?php

declare(strict_types=1);

use App\Livewire\Admin\Auditoria\HistoricoRegistro;
use App\Livewire\Admin\Usuarios\FormUsuario;
use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Models\AdminUser;
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

it('lista a trilha de mudanças do registro com o diff', function () {
    $this->actingAs($this->admin, 'admin');

    $alvo = criarAdminUser('trilha@teste.com');
    $alvo->update(['nome' => 'Trilha Editada']);

    Livewire::withoutLazyLoading()->test(HistoricoRegistro::class, [
        'subjectType' => AdminUser::class,
        'subjectId' => $alvo->id,
    ])
        ->assertOk()
        ->assertSee('Histórico de mudanças')
        ->assertSee('created')
        ->assertSee('updated')
        ->assertSee('Trilha Editada');
});

it('renderiza a barra de paginação com aria-label em PT-BR quando a trilha tem mais de uma página', function () {
    $this->actingAs($this->admin, 'admin');

    $alvo = criarAdminUser('trilha-paginada@teste.com');

    // > 10 atividades (perPage) para forçar a barra; a paginação Livewire só a
    // exibe quando hasPages() é true. Guarda a tradução PT-BR (CLAUDE.md §4) do
    // aria-label dos links de página na tela real de auditoria.
    foreach (range(1, 12) as $i) {
        activity()
            ->performedOn($alvo)
            ->event('updated')
            ->log("alteração {$i}");
    }

    Livewire::withoutLazyLoading()->test(HistoricoRegistro::class, [
        'subjectType' => AdminUser::class,
        'subjectId' => $alvo->id,
    ])
        ->assertOk()
        ->assertSeeHtml('aria-label="Ir para a página')
        ->assertDontSeeHtml('aria-label="Go to page');
});

it('nega o componente para quem não tem auditoria.visualizar', function () {
    $gestor = criarAdminUser('gestor@teste.com');
    $gestor->assignRole('gestor');

    Livewire::withoutLazyLoading()
        ->actingAs($gestor, 'admin')
        ->test(HistoricoRegistro::class, [
            'subjectType' => AdminUser::class,
            'subjectId' => $gestor->id,
        ])
        ->assertForbidden();
});

it('exibe a aba Histórico no FormUsuario apenas com permissão', function () {
    $alvo = criarAdminUser('comaba@teste.com');

    Livewire::actingAs($this->admin, 'admin')
        ->test(FormUsuario::class, ['usuario' => $alvo->id])
        ->assertSee('Histórico');
});
