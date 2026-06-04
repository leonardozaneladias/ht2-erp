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
