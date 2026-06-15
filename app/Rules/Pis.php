<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Pis implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pis = preg_replace('/\D/', '', (string) $value);

        if (strlen($pis) !== 11) {
            $fail('O :attribute não é um PIS/PASEP válido.');

            return;
        }

        // Rejeita sequências de dígitos repetidos (ex.: 111.11111.11-1)
        if (preg_match('/^(\d)\1{10}$/', $pis)) {
            $fail('O :attribute não é um PIS/PASEP válido.');

            return;
        }

        // Dígito verificador — pesos: [3,2,9,8,7,6,5,4,3,2] para os 10 primeiros dígitos
        $pesos = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $pis[$i] * $pesos[$i];
        }

        $resto = $soma % 11;
        $digito = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $pis[10] !== $digito) {
            $fail('O :attribute não é um PIS/PASEP válido.');
        }
    }
}
