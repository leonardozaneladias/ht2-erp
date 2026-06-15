<?php

declare(strict_types=1);

use App\Exceptions\Referencia\ImportacaoReferenciaException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\SeederReferenciaFake;

uses(RefreshDatabase::class);

$fixture = fn (string $nome): string => __DIR__ . '/../../Fixtures/referencia/' . $nome;

beforeEach(function () {
    Schema::create('ref_fixture', function (Blueprint $table): void {
        $table->id();
        $table->string('chave')->unique();
        $table->string('valor')->nullable();
        $table->timestamps();
    });
});

it('conta lidas/inseridas/puladas e ignora cabeçalho, brancas e inválidas', function () use ($fixture) {
    $seeder = new SeederReferenciaFake($fixture('fixture_base.csv'));
    $seeder->run();

    expect($seeder->lidas)->toBe(3)
        ->and($seeder->inseridos)->toBe(2)
        ->and($seeder->pulados)->toBe(1)
        ->and(DB::table('ref_fixture')->count())->toBe(1) // válidas únicas
        ->and(DB::table('ref_fixture')->where('chave', 'A1')->value('valor'))->toBe('Atualizado');
});

it('é idempotente: re-rodar não duplica e mantém os contadores', function () use ($fixture) {
    (new SeederReferenciaFake($fixture('fixture_base.csv')))->run();

    $seeder = new SeederReferenciaFake($fixture('fixture_base.csv'));
    $seeder->run();

    expect(DB::table('ref_fixture')->count())->toBe(1)
        ->and($seeder->inseridos)->toBe(2)
        ->and($seeder->pulados)->toBe(1);
});

it('upsert atualiza o dado in-place e preserva o created_at no conflito', function () use ($fixture) {
    (new SeederReferenciaFake($fixture('fixture_base.csv')))->run();

    // Simula um registro antigo: dado defasado + created_at no passado.
    DB::table('ref_fixture')->where('chave', 'A1')->update([
        'valor' => 'DEFASADO',
        'created_at' => '2020-01-01 00:00:00',
    ]);

    (new SeederReferenciaFake($fixture('fixture_base.csv')))->run();

    $linha = DB::table('ref_fixture')->where('chave', 'A1')->firstOrFail();
    expect($linha->valor)->toBe('Atualizado')                     // dado re-sincronizado
        ->and((string) $linha->created_at)->toContain('2020-01-01'); // created_at preservado
});

it('aborta quando um arquivo com dados não rende nenhuma linha', function () use ($fixture) {
    $seeder = new SeederReferenciaFake($fixture('fixture_zero.csv'));

    expect(fn () => $seeder->run())->toThrow(ImportacaoReferenciaException::class);
    expect(DB::table('ref_fixture')->count())->toBe(0);
});

it('aborta quando o cabeçalho declarado diverge do CSV', function () use ($fixture) {
    $seeder = new SeederReferenciaFake($fixture('fixture_cabecalho.csv'), ['chave', 'valor']);

    expect(fn () => $seeder->run())->toThrow(ImportacaoReferenciaException::class);
});

it('aborta quando o total fica abaixo do mínimo esperado', function () use ($fixture) {
    $seeder = new SeederReferenciaFake($fixture('fixture_base.csv'), minimo: 5);

    expect(fn () => $seeder->run())->toThrow(ImportacaoReferenciaException::class);
});
