<?php

declare(strict_types=1);

use App\Enums\Referencia\RegiaoBrasil;
use App\Enums\Referencia\TipoCfop;

it('RegiaoBrasil::peloCodigoIbge deriva a região pelo 1º dígito', function () {
    expect(RegiaoBrasil::peloCodigoIbge('35'))->toBe(RegiaoBrasil::Sudeste)
        ->and(RegiaoBrasil::peloCodigoIbge('11'))->toBe(RegiaoBrasil::Norte)
        ->and(RegiaoBrasil::peloCodigoIbge('53'))->toBe(RegiaoBrasil::CentroOeste);
});

it('RegiaoBrasil::peloCodigoIbge lança ValueError em código inválido', function () {
    expect(fn () => RegiaoBrasil::peloCodigoIbge('99'))->toThrow(ValueError::class);
});

it('TipoCfop::peloCodigo deriva o tipo pelo 1º dígito', function () {
    expect(TipoCfop::peloCodigo('1102'))->toBe(TipoCfop::Entrada)
        ->and(TipoCfop::peloCodigo('5102'))->toBe(TipoCfop::Saida);
});

it('TipoCfop::peloCodigo lança ValueError em CFOP inválido', function () {
    expect(fn () => TipoCfop::peloCodigo('9999'))->toThrow(ValueError::class);
});
