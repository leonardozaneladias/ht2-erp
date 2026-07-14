<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Título de eleitor: 8 dígitos sequenciais + 2 de UF (01–28) + 2 verificadores.
 *
 * DV1 = módulo 11 dos 8 primeiros com pesos 2..9; DV2 = módulo 11 de (UF1, UF2, DV1)
 * com pesos 7, 8, 9. Resto 10 → 0; resto 0 em título de SP (01) ou MG (02) → 1
 * (convenção histórica do TSE). Par JS: validarTituloEleitor em
 * resources/js/admin/validators.js — o contrato é o fixture
 * tests/Fixtures/documentos-dv.json.
 */
final class TituloEleitor implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $titulo = preg_replace('/\D/', '', (string) $value);

        if (strlen($titulo) !== 12 || preg_match('/^(\d)\1{11}$/', $titulo)) {
            $fail('O :attribute não é um título de eleitor válido.');

            return;
        }

        $uf = (int) substr($titulo, 8, 2);

        if ($uf < 1 || $uf > 28) {
            $fail('O :attribute não é um título de eleitor válido.');

            return;
        }

        $spMg = $uf === 1 || $uf === 2;

        $digito = static function (int $soma) use ($spMg): int {
            $resto = $soma % 11;

            if ($resto === 10) {
                return 0;
            }

            if ($resto === 0 && $spMg) {
                return 1;
            }

            return $resto;
        };

        $soma1 = 0;

        for ($i = 0; $i < 8; $i++) {
            $soma1 += (int) $titulo[$i] * ($i + 2);
        }

        $dv1 = $digito($soma1);

        if ((int) $titulo[10] !== $dv1) {
            $fail('O :attribute não é um título de eleitor válido.');

            return;
        }

        $soma2 = (int) $titulo[8] * 7 + (int) $titulo[9] * 8 + $dv1 * 9;

        if ((int) $titulo[11] !== $digito($soma2)) {
            $fail('O :attribute não é um título de eleitor válido.');
        }
    }
}
