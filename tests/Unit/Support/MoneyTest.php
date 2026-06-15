<?php

declare(strict_types=1);

use App\Support\Money\Money;

// ---- Construção --------------------------------------------------------------

it('cria Money a partir de centavos', function (): void {
    $m = Money::deCentavos(1990);

    expect($m->centavos)->toBe(1990);
});

it('cria Money zero via factory', function (): void {
    expect(Money::zero()->centavos)->toBe(0);
    expect(Money::zero()->ehZero())->toBeTrue();
});

it('rejeita centavos negativos', function (): void {
    expect(fn () => Money::deCentavos(-1))->toThrow(InvalidArgumentException::class);
});

// ---- Aritmética --------------------------------------------------------------

it('soma dois valores', function (): void {
    $a = Money::deCentavos(1000);
    $b = Money::deCentavos(500);

    expect($a->somar($b)->centavos)->toBe(1500);
});

it('subtrai dois valores', function (): void {
    $a = Money::deCentavos(2000);
    $b = Money::deCentavos(500);

    expect($a->subtrair($b)->centavos)->toBe(1500);
});

it('rejeita subtração com resultado negativo', function (): void {
    $a = Money::deCentavos(100);
    $b = Money::deCentavos(200);

    expect(fn () => $a->subtrair($b))->toThrow(InvalidArgumentException::class);
});

it('multiplica por int', function (): void {
    $m = Money::deCentavos(1000);

    expect($m->multiplicar(3)->centavos)->toBe(3000);
});

it('multiplica por float com arredondamento', function (): void {
    $m = Money::deCentavos(1000);

    // 1000 * 1.5 = 1500
    expect($m->multiplicar(1.5)->centavos)->toBe(1500);
});

it('é imutável — operações retornam nova instância', function (): void {
    $original = Money::deCentavos(500);
    $novo = $original->somar(Money::deCentavos(100));

    expect($original->centavos)->toBe(500);
    expect($novo->centavos)->toBe(600);
    expect($original)->not->toBe($novo);
});

// ---- Comparação --------------------------------------------------------------

it('compara igualdade', function (): void {
    expect(Money::deCentavos(100)->igual(Money::deCentavos(100)))->toBeTrue();
    expect(Money::deCentavos(100)->igual(Money::deCentavos(200)))->toBeFalse();
});

it('compara maior e menor', function (): void {
    $a = Money::deCentavos(200);
    $b = Money::deCentavos(100);

    expect($a->maiorQue($b))->toBeTrue();
    expect($b->menorQue($a))->toBeTrue();
    expect($a->menorQue($b))->toBeFalse();
});

// ---- Conversão e formatação --------------------------------------------------

it('converte centavos para reais', function (): void {
    expect(Money::deCentavos(1990)->reais())->toBe(19.9);
});

it('formata como BRL', function (): void {
    $formatted = Money::deCentavos(199000)->formatarBRL();

    expect($formatted)->toContain('1.990');
});

it('__toString retorna formatação BRL', function (): void {
    $m = Money::deCentavos(5000);

    expect((string) $m)->toContain('50');
});
