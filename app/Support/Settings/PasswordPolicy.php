<?php

declare(strict_types=1);

namespace App\Support\Settings;

use HT2ML\Core\Settings\SegurancaSettings;
use Illuminate\Validation\Rules\Password;

/**
 * Constrói a regra de validação de senha a partir da política configurada em
 * SegurancaSettings. Use em qualquer ponto que valide senhas (cadastro de
 * usuário, redefinição, setup wizard) para manter a política única.
 */
final class PasswordPolicy
{
    public static function rule(): Password
    {
        $settings = app(SegurancaSettings::class);

        $rule = Password::min(max(1, $settings->senha_min_caracteres));

        if ($settings->senha_exige_maiuscula) {
            $rule->mixedCase();
        }

        if ($settings->senha_exige_numero) {
            $rule->numbers();
        }

        if ($settings->senha_exige_especial) {
            $rule->symbols();
        }

        return $rule;
    }

    public static function descricao(): string
    {
        $settings = app(SegurancaSettings::class);

        $exigencias = ["mínimo de {$settings->senha_min_caracteres} caracteres"];

        if ($settings->senha_exige_maiuscula) {
            $exigencias[] = 'letras maiúsculas e minúsculas';
        }

        if ($settings->senha_exige_numero) {
            $exigencias[] = 'números';
        }

        if ($settings->senha_exige_especial) {
            $exigencias[] = 'símbolos';
        }

        return 'A senha deve conter ' . implode(', ', $exigencias) . '.';
    }
}
