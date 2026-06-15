<?php

declare(strict_types=1);

use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;

describe('Cpf Rule', function (): void {
    function validarCpf(mixed $valor): bool
    {
        return Validator::make(
            ['cpf' => $valor],
            ['cpf' => [new Cpf]],
        )->passes();
    }

    it('aceita CPF válido com máscara', function (): void {
        expect(validarCpf('111.444.777-35'))->toBeTrue();
    });

    it('aceita CPF válido sem máscara', function (): void {
        expect(validarCpf('11144477735'))->toBeTrue();
    });

    it('rejeita CPF com dígito verificador errado', function (): void {
        expect(validarCpf('111.444.777-36'))->toBeFalse();
    });

    it('rejeita CPF com sequência repetida', function (): void {
        expect(validarCpf('111.111.111-11'))->toBeFalse();
    });

    it('rejeita CPF muito curto', function (): void {
        expect(validarCpf('123.456.789'))->toBeFalse();
    });

    it('rejeita CPF com tamanho errado após strip', function (): void {
        expect(validarCpf('123456'))->toBeFalse();
    });
});
