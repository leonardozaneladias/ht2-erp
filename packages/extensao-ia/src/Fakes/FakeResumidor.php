<?php

declare(strict_types=1);

namespace HT2ML\Ia\Fakes;

use HT2ML\Ia\Contracts\ResumidorInterface;
use Illuminate\Support\Str;

/**
 * Resumidor de desenvolvimento. Devolve algo derivado do conteúdo real, e não
 * um texto fixo: o teste de contrato consegue provar que o resumo veio DAQUELE
 * texto e não de outro, e em que idioma foi pedido.
 */
final class FakeResumidor implements ResumidorInterface
{
    public function resumir(string $titulo, string $corpo, string $idioma = 'pt'): array
    {
        return ['texto' => sprintf(
            '[resumo:%s] %s — %s',
            $idioma,
            $titulo,
            Str::limit(trim(strip_tags($corpo)), 140),
        )];
    }

    public function identificador(): string
    {
        return 'fake';
    }
}
