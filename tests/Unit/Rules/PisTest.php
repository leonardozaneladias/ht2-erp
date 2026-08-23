<?php

declare(strict_types=1);

use HT2ML\Core\Rules\Pis;
use Illuminate\Support\Facades\Validator;

describe('Pis Rule', function (): void {
    function validarPis(mixed $valor): bool
    {
        return Validator::make(
            ['pis' => $valor],
            ['pis' => [new Pis]],
        )->passes();
    }

    it('aceita PIS válido sem máscara', function (): void {
        expect(validarPis('12345678919'))->toBeTrue();
    });

    it('aceita PIS válido com máscara', function (): void {
        expect(validarPis('123.45678.91-9'))->toBeTrue();
    });

    it('rejeita PIS com sequência repetida', function (): void {
        expect(validarPis('111.11111.11-1'))->toBeFalse();
    });

    it('rejeita PIS com dígito verificador errado', function (): void {
        expect(validarPis('12345678910'))->toBeFalse();
    });

    it('rejeita PIS muito curto', function (): void {
        expect(validarPis('123456'))->toBeFalse();
    });
});
