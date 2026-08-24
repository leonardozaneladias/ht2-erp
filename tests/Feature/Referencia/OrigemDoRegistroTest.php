<?php

declare(strict_types=1);

use Database\Seeders\Referencia\CargoSeeder;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Referencia\Cargo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Origem do registro nos catálogos de referência
|--------------------------------------------------------------------------
|
| Antes desta separação, o `referencia:sync` revertia edição manual em
| silêncio: o upsert casava pela chave natural e sobrescrevia o que o usuário
| tinha mudado. A correção não foi reconciliar as duas escritas — foi impedir
| que elas disputem a mesma linha.
|
*/

it('registro criado pelo CRUD nasce como cadastro próprio', function () {
    $cargo = Cargo::create(['codigo_cbo' => '999999', 'descricao' => 'Cargo próprio', 'ativo' => true]);

    expect($cargo->origem)->toBe(OrigemRegistro::Manual)
        ->and($cargo->sincronizado())->toBeFalse()
        ->and($cargo->cadastradoAqui())->toBeTrue();
});

it('o sync marca como sincronizado o que ele traz', function () {
    $this->seed(CargoSeeder::class);

    $doCsv = Cargo::query()->sincronizados()->first();

    expect($doCsv)->not->toBeNull()
        ->and($doCsv->origem)->toBe(OrigemRegistro::Sincronizado);
});

it('o sync não sobrescreve registro cadastrado aqui com a mesma chave natural', function () {
    // Ocupa uma chave natural que o CSV também traz.
    $this->seed(CargoSeeder::class);
    $codigo = (string) Cargo::query()->sincronizados()->value('codigo_cbo');

    Cargo::query()->where('codigo_cbo', $codigo)->forceDelete();
    Cargo::create(['codigo_cbo' => $codigo, 'descricao' => 'Descrição do cliente', 'ativo' => true]);

    $this->seed(CargoSeeder::class);

    $registro = Cargo::query()->where('codigo_cbo', $codigo)->first();

    expect($registro->descricao)->toBe('Descrição do cliente')
        ->and($registro->origem)->toBe(OrigemRegistro::Manual);
});

it('o padrão do banco é sincronizado, para o que já existia antes desta coluna', function () {
    DB::table('cargos')->insert([
        'codigo_cbo' => '888888',
        'descricao' => 'Legado',
        'ativo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(Cargo::query()->where('codigo_cbo', '888888')->first()->origem)
        ->toBe(OrigemRegistro::Sincronizado);
});

it('nem o super-admin exclui registro sincronizado pelo grid', function () {
    $this->seed(Database\Seeders\RolePermissionSeeder::class);
    $this->seed(CargoSeeder::class);

    $admin = criarAdminUser('super@teste.com');
    $admin->assignRole('super-admin');

    $sincronizado = Cargo::query()->sincronizados()->first();
    $proprio = Cargo::create(['codigo_cbo' => '777777', 'descricao' => 'Próprio', 'ativo' => true]);

    // Super-admin bypassa policies via Gate::before — só a guarda do ComLixeira
    // o segura, e é exatamente isso que este teste prova.
    Livewire\Livewire::actingAs($admin, 'admin')
        ->test(App\Livewire\Admin\Referencia\CargoTable::class)
        ->call('excluir', $sincronizado->id);

    expect(Cargo::query()->whereKey($sincronizado->id)->exists())->toBeTrue();

    Livewire\Livewire::actingAs($admin, 'admin')
        ->test(App\Livewire\Admin\Referencia\CargoTable::class)
        ->call('excluir', $proprio->id);

    expect(Cargo::query()->whereKey($proprio->id)->exists())->toBeFalse()
        ->and(Cargo::withTrashed()->whereKey($proprio->id)->exists())->toBeTrue();
});
