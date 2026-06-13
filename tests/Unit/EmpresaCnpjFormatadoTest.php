<?php

declare(strict_types=1);

use App\Models\Empresa;

it('formata CNPJ de 14 dígitos com máscara', function () {
    $empresa = new Empresa(['cnpj' => '15156249000168']);

    expect($empresa->cnpjFormatado)->toBe('15.156.249/0001-68');
});

it('normaliza CNPJ já mascarado para a máscara canônica', function () {
    $empresa = new Empresa(['cnpj' => '15.156.249/0001-68']);

    expect($empresa->cnpjFormatado)->toBe('15.156.249/0001-68');
});

it('retorna null quando o CNPJ está vazio', function (?string $valor) {
    $empresa = new Empresa(['cnpj' => $valor]);

    expect($empresa->cnpjFormatado)->toBeNull();
})->with([null, '']);

it('mantém o valor original quando não há 14 dígitos', function () {
    $empresa = new Empresa(['cnpj' => '123']);

    expect($empresa->cnpjFormatado)->toBe('123');
});
