<?php

declare(strict_types=1);

use App\Casts\MoneyCast;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;

// ---- get() ------------------------------------------------------------------

it('converte int do banco para Money VO', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    $result = $cast->get($model, 'preco', 1990, []);

    expect($result)->toBeInstanceOf(Money::class);
    expect($result->centavos)->toBe(1990);
});

it('retorna null quando valor do banco é null', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    expect($cast->get($model, 'preco', null, []))->toBeNull();
});

// ---- set() ------------------------------------------------------------------

it('converte Money VO para int', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    $result = $cast->set($model, 'preco', Money::deCentavos(5000), []);

    expect($result)->toBe(5000);
});

it('aceita int direto no set()', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    expect($cast->set($model, 'preco', 1234, []))->toBe(1234);
});

it('rejeita int negativo no set()', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    expect(fn () => $cast->set($model, 'preco', -100, []))->toThrow(InvalidArgumentException::class);
});

it('rejeita tipo inválido no set()', function (): void {
    $cast = new MoneyCast;
    $model = Mockery::mock(Model::class);

    expect(fn () => $cast->set($model, 'preco', '1990', []))->toThrow(InvalidArgumentException::class);
});
