<?php

declare(strict_types=1);

use App\Models\AdminUser;
use App\Models\Empresa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('semeia a empresa demonstração com matriz e vincula os admins', function () {
    $this->seed(DatabaseSeeder::class);

    $empresa = Empresa::query()->where('nome', 'Empresa Demonstração')->first();

    expect($empresa)->not->toBeNull()
        ->and($empresa->ativo)->toBeTrue()
        ->and($empresa->filiais()->where('e_matriz', true)->where('nome', 'Matriz')->exists())->toBeTrue();

    $admin = AdminUser::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($admin->temAcessoAEmpresa($empresa->id))->toBeTrue()
        ->and($admin->empresa_ativa_id)->toBe($empresa->id);
});

it('semeia 2+ empresas e habilita o filtro multi-empresa para o gestor', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Empresa::query()->count())->toBeGreaterThanOrEqual(2);

    $gestor = AdminUser::query()->where('email', 'gestor@example.com')->firstOrFail();

    // O gestor acessa 2+ empresas e tem exemplos.listar em todas elas (papel
    // global) → o filtro multi-empresa fica de fato disponível na demo.
    expect($gestor->empresasAcessiveis()->count())->toBeGreaterThanOrEqual(2);

    $resolver = app(App\Services\Admin\AccessResolver::class);

    $elegiveis = Empresa::query()
        ->pluck('id')
        ->filter(fn (int $id): bool => $resolver->permiteNaEmpresa($gestor, 'exemplos.listar', $id));

    expect($elegiveis->count())->toBeGreaterThanOrEqual(2);
});
