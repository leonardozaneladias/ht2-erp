<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11) {
            $fail('O :attribute não é um CPF válido.');

            return;
        }

        // Rejeita sequências de dígitos repetidos (ex.: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O :attribute não é um CPF válido.');

            return;
        }

        // Primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }

        $resto = $soma % 11;
        $dig1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[9] !== $dig1) {
            $fail('O :attribute não é um CPF válido.');

            return;
        }

        // Segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }

        $resto = $soma % 11;
        $dig2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[10] !== $dig2) {
            $fail('O :attribute não é um CPF válido.');
        }
    }
}
