<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (strlen($cnpj) !== 14) {
            $fail('O :attribute não é um CNPJ válido.');

            return;
        }

        // Rejeita sequências de dígitos repetidos (ex.: 11.111.111/1111-11)
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O :attribute não é um CNPJ válido.');

            return;
        }

        // Primeiro dígito verificador — pesos: [5,4,3,2,9,8,7,6,5,4,3,2]
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesos1[$i];
        }

        $resto = $soma % 11;
        $dig1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[12] !== $dig1) {
            $fail('O :attribute não é um CNPJ válido.');

            return;
        }

        // Segundo dígito verificador — pesos: [6,5,4,3,2,9,8,7,6,5,4,3,2]
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesos2[$i];
        }

        $resto = $soma % 11;
        $dig2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[13] !== $dig2) {
            $fail('O :attribute não é um CNPJ válido.');
        }
    }
}
