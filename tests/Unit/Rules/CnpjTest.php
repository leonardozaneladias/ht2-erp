<?php

declare(strict_types=1);

use HT2ML\Core\Rules\Cnpj;
use Illuminate\Support\Facades\Validator;

describe('Cnpj Rule', function (): void {
    function validarCnpj(mixed $valor): bool
    {
        return Validator::make(
            ['cnpj' => $valor],
            ['cnpj' => [new Cnpj]],
        )->passes();
    }

    it('aceita CNPJ válido com máscara', function (): void {
        expect(validarCnpj('11.222.333/0001-81'))->toBeTrue();
    });

    it('aceita CNPJ válido sem máscara', function (): void {
        expect(validarCnpj('11222333000181'))->toBeTrue();
    });

    it('rejeita CNPJ com dígito verificador errado', function (): void {
        expect(validarCnpj('11.222.333/0001-82'))->toBeFalse();
    });

    it('rejeita CNPJ com sequência repetida', function (): void {
        expect(validarCnpj('11.111.111/1111-11'))->toBeFalse();
    });

    it('rejeita CNPJ muito curto', function (): void {
        expect(validarCnpj('11.222.333/0001'))->toBeFalse();
    });

    it('rejeita CNPJ com tamanho errado após strip', function (): void {
        expect(validarCnpj('123456'))->toBeFalse();
    });
});
