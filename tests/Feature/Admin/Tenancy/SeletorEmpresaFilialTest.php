<?php

declare(strict_types=1);

use App\Livewire\Admin\Tenancy\SeletorEmpresaFilial;
use App\Models\Empresa;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('troca a empresa ativa pelo seletor', function () {
    $a = Empresa::create(['nome' => 'Empresa A', 'ativo' => true]);
    $b = Empresa::create(['nome' => 'Empresa B', 'ativo' => true]);
    $user = criarAdminUser('sel@teste.com');
    $user->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);
    $user->empresasAcessiveis()->attach($b->id, ['todas_filiais' => true]);

    $this->actingAs($user, 'admin');
    app(TenantContext::class)->definirEmpresa($a->id);

    Livewire::test(SeletorEmpresaFilial::class)
        ->assertSee('Empresa A')
        ->assertSee('Empresa B')
        ->call('definirEmpresa', $b->id);

    expect(app(TenantContext::class)->empresaAtivaId())->toBe($b->id)
        ->and($user->fresh()->empresa_ativa_id)->toBe($b->id);
});

it('exibe apenas o nome quando o usuário tem uma única empresa', function () {
    $a = Empresa::create(['nome' => 'Empresa Única', 'ativo' => true]);
    $user = criarAdminUser('uni@teste.com');
    $user->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);

    $this->actingAs($user, 'admin');
    app(TenantContext::class)->definirEmpresa($a->id);

    Livewire::test(SeletorEmpresaFilial::class)
        ->assertSee('Empresa Única')
        ->assertDontSee('definirEmpresa(', escape: false);
});

it('troca a filial ativa quando há mais de uma', function () {
    $a = Empresa::create(['nome' => 'A', 'ativo' => true]);
    $user = criarAdminUser('fil@teste.com');
    $user->empresasAcessiveis()->attach($a->id, ['todas_filiais' => true]);
    $a->filiais()->create(['nome' => 'Matriz', 'e_matriz' => true, 'ativo' => true]);
    $f2 = $a->filiais()->create(['nome' => 'Filial 2', 'ativo' => true]);

    $this->actingAs($user, 'admin');
    app(TenantContext::class)->definirEmpresa($a->id);

    Livewire::test(SeletorEmpresaFilial::class)
        ->assertSee('Matriz')
        ->assertSee('Filial 2')
        ->call('definirFilial', $f2->id);

    expect(app(TenantContext::class)->filialAtivaId())->toBe($f2->id);
});
