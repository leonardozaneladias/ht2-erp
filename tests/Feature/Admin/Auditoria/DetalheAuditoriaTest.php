<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use HT2ML\Core\Livewire\Admin\Auditoria\IndexAuditoria;
use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Models\Empresa;
use HT2ML\Core\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

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

it('abre o drawer com o diff antes/depois e o contexto', function () {
    $this->actingAs($this->admin, 'admin');

    $alvo = criarAdminUser('diff@teste.com');
    $alvo->update(['nome' => 'Nome Pós Edição']);

    $log = Activity::query()
        ->where('event', 'updated')
        ->where('subject_id', $alvo->id)
        ->latest('id')
        ->firstOrFail();

    Livewire::test(IndexAuditoria::class)
        ->call('detalhar', $log->id)
        ->assertSet('detalheId', $log->id)
        ->assertDispatched('auditoria-abrir-detalhe')
        ->assertSee('Mudanças de dados')
        ->assertSee('Usuário Teste')
        ->assertSee('Nome Pós Edição');
});

it('mostra evento de domínio sem diff com as propriedades', function () {
    $this->actingAs($this->admin, 'admin');

    activity('auth')->event('login')->withProperties(['2fa' => true])->log('Login realizado');
    $log = Activity::query()->latest('id')->firstOrFail();

    Livewire::test(IndexAuditoria::class)
        ->call('detalhar', $log->id)
        ->assertSee('Evento de domínio sem diff de atributos');
});

it('não abre detalhe de outra empresa para usuário sem visão cross-empresa', function () {
    $empresaA = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $empresaB = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);

    app(TenantContext::class)->definirEmpresa($empresaB->id);
    activity('test')->log('segredo da empresa B');
    $logB = Activity::query()->latest('id')->firstOrFail();

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor-auditoria', 50)->givePermissionTo(
        Permission::findOrCreate('auditoria.visualizar', 'admin'),
    );
    $gestor->assignRole('gestor-auditoria');

    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => $empresaA->id]);

    Livewire::test(IndexAuditoria::class)
        ->call('detalhar', $logB->id)
        ->assertSet('detalheId', null)
        ->assertNotDispatched('auditoria-abrir-detalhe')
        ->assertDontSee('segredo da empresa B');
});
