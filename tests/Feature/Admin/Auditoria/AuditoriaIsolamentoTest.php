<?php

declare(strict_types=1);

use App\Livewire\Admin\Auditoria\AuditoriaTable;
use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('publica a permissão auditoria.todas-empresas via access:sync', function (): void {
    Artisan::call('access:sync');

    expect(Permission::where('name', 'auditoria.todas-empresas')->where('guard_name', 'admin')->exists())
        ->toBeTrue();
});

/** Cria 3 atividades-âncora: empresa A, empresa B e sem empresa. */
function semearAuditoria(): array
{
    $a = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
    $ctx = app(TenantContext::class);

    $ctx->definirEmpresa($a->id);
    activity('test')->log('evento-empresa-A');
    $ctx->definirEmpresa($b->id);
    activity('test')->log('evento-empresa-B');
    $ctx->limpar();
    activity('test')->log('evento-sem-empresa');

    return [$a, $b];
}

it('isola: gestor vê só a empresa ativa, não outra nem sem-empresa', function (): void {
    [$a] = semearAuditoria();

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor', 50)->givePermissionTo(
        Permission::findOrCreate('auditoria.visualizar', 'admin'),
    );
    $gestor->assignRole('gestor');

    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertDontSee('evento-empresa-B')
        ->assertDontSee('evento-sem-empresa');
});

it('super-admin vê tudo (inclusive sem-empresa)', function (): void {
    semearAuditoria();

    $super = criarAdminUser('super@teste.com');
    criarRoleAdmin('super-admin', 100);
    $super->assignRole('super-admin');

    $this->actingAs($super, 'admin');

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertSee('evento-empresa-B')
        ->assertSee('evento-sem-empresa');
});

it('portador de auditoria.todas-empresas vê tudo', function (): void {
    semearAuditoria();

    $auditor = criarAdminUser('auditor@teste.com');
    criarRoleAdmin('auditor', 30)->givePermissionTo([
        Permission::findOrCreate('auditoria.visualizar', 'admin'),
        Permission::findOrCreate('auditoria.todas-empresas', 'admin'),
    ]);
    $auditor->assignRole('auditor');

    $this->actingAs($auditor, 'admin');
    session(['tenant.empresa_id' => null]);

    Livewire::test(AuditoriaTable::class)
        ->assertSee('evento-empresa-A')
        ->assertSee('evento-empresa-B')
        ->assertSee('evento-sem-empresa');
});

it('isolado sem empresa ativa não vê nada', function (): void {
    semearAuditoria();

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor', 50)->givePermissionTo(
        Permission::findOrCreate('auditoria.visualizar', 'admin'),
    );
    $gestor->assignRole('gestor');

    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => null]);

    Livewire::test(AuditoriaTable::class)
        ->assertDontSee('evento-empresa-A')
        ->assertDontSee('evento-empresa-B')
        ->assertDontSee('evento-sem-empresa');
});

it('escopa as opções de filtro por empresa (não vaza categorias de outras)', function (): void {
    $a = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
    $ctx = app(TenantContext::class);
    $ctx->definirEmpresa($a->id);
    activity('auditoria-a')->log('x');
    $ctx->definirEmpresa($b->id);
    activity('auditoria-b')->log('y');

    $gestor = criarAdminUser('gestor@teste.com');
    criarRoleAdmin('gestor', 50)->givePermissionTo(
        Permission::findOrCreate('auditoria.visualizar', 'admin'),
    );
    $gestor->assignRole('gestor');
    $this->actingAs($gestor, 'admin');
    session(['tenant.empresa_id' => $a->id]);

    // opcoes() é protegido; chamamos via reflexão para validar o escopo das opções
    // de filtro (não basta isolar as linhas — os dropdowns também não podem vazar).
    $componente = Livewire::test(AuditoriaTable::class)->instance();
    $metodo = new ReflectionMethod($componente, 'opcoes');
    $metodo->setAccessible(true);
    $logNames = collect($metodo->invoke($componente, 'log_name'))->pluck('valor')->all();

    expect($logNames)->toContain('auditoria-a')
        ->not->toContain('auditoria-b');
});
