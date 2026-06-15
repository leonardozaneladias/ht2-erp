<?php

declare(strict_types=1);

use App\Support\Money\Money;

// ---- Construção --------------------------------------------------------------

it('cria Money a partir de centavos', function (): void {
    $m = Money::fromCentavos(1990);

    expect($m->centavos())->toBe(1990);
});

it('cria Money zero via factory', function (): void {
    expect(Money::zero()->centavos())->toBe(0);
    expect(Money::zero()->eZero())->toBeTrue();
});

it('rejeita centavos negativos', function (): void {
    expect(fn () => Money::fromCentavos(-1))->toThrow(InvalidArgumentException::class);
});

it('fromReais converte reais para centavos', fn () => expect(Money::fromReais(19.90)->centavos())->toBe(1990));

it('fromReais arredonda', fn () => expect(Money::fromReais(19.905)->centavos())->toBe(1991));

it('centavos retorna int', fn () => expect(Money::fromCentavos(500)->centavos())->toBe(500));

it('toInt retorna centavos', fn () => expect(Money::fromCentavos(999)->toInt())->toBe(999));

// ---- Aritmética --------------------------------------------------------------

it('soma dois valores', function (): void {
    $a = Money::fromCentavos(1000);
    $b = Money::fromCentavos(500);

    expect($a->mais($b)->centavos())->toBe(1500);
});

it('subtrai dois valores', function (): void {
    $a = Money::fromCentavos(2000);
    $b = Money::fromCentavos(500);

    expect($a->menos($b)->centavos())->toBe(1500);
});

it('rejeita subtração com resultado negativo', function (): void {
    $a = Money::fromCentavos(100);
    $b = Money::fromCentavos(200);

    expect(fn () => $a->menos($b))->toThrow(InvalidArgumentException::class);
});

it('multiplica por int', function (): void {
    $m = Money::fromCentavos(1000);

    expect($m->multiplicar(3)->centavos())->toBe(3000);
});

it('multiplica por float com arredondamento', function (): void {
    $m = Money::fromCentavos(1000);

    // 1000 * 1.5 = 1500
    expect($m->multiplicar(1.5)->centavos())->toBe(1500);
});

it('é imutável — operações retornam nova instância', function (): void {
    $original = Money::fromCentavos(500);
    $novo = $original->mais(Money::fromCentavos(100));

    expect($original->centavos())->toBe(500);
    expect($novo->centavos())->toBe(600);
    expect($original)->not->toBe($novo);
});

// ---- Comparação --------------------------------------------------------------

it('compara igualdade', function (): void {
    expect(Money::fromCentavos(100)->igual(Money::fromCentavos(100)))->toBeTrue();
    expect(Money::fromCentavos(100)->igual(Money::fromCentavos(200)))->toBeFalse();
});

it('compara maior e menor', function (): void {
    $a = Money::fromCentavos(200);
    $b = Money::fromCentavos(100);

    expect($a->maiorQue($b))->toBeTrue();
    expect($b->menorQue($a))->toBeTrue();
    expect($a->menorQue($b))->toBeFalse();
});

// ---- Conversão e formatação --------------------------------------------------

it('converte centavos para reais', function (): void {
    expect(Money::fromCentavos(1990)->reais())->toBe(19.9);
});

it('formata como BRL', function (): void {
    $formatted = Money::fromCentavos(199000)->formatado();

    expect($formatted)->toContain('1.990');
});

it('__toString retorna formatação BRL', function (): void {
    $m = Money::fromCentavos(5000);

    expect((string) $m)->toContain('50');
});
