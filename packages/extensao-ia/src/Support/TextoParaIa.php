<?php

declare(strict_types=1);

namespace HT2ML\Ia\Support;

use Illuminate\Support\Str;

/**
 * Teto do texto enviado a um provedor de IA.
 *
 * Corta o que é ENVIADO, não o que é gravado. Limitar a validação do campo de
 * origem seria a correção errada: mudaria o que o usuário pode escrever e
 * quebraria a edição de registros já mais longos que o teto. O problema é
 * custo com terceiro, não gente escrevendo demais.
 */
final class TextoParaIa
{
    public static function limite(): int
    {
        return max(1, (int) config('ia.limite_caracteres', 20000));
    }

    public static function limitar(string $texto): string
    {
        return Str::limit($texto, self::limite(), '…');
    }
}
