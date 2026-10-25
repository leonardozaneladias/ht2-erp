<?php

declare(strict_types=1);

namespace HT2ML\Ia\Fakes;

use HT2ML\Ia\Contracts\AssistenteInterface;

/**
 * Assistente de desenvolvimento: ecoa a pergunta, o idioma e as chaves do
 * contexto. O idioma e o contexto aparecem na resposta de propósito — um fake
 * que só recebesse os parâmetros, sem efeito observável, deixaria o teste sem
 * como provar que a informação chegou até o provedor.
 */
final class FakeAssistente implements AssistenteInterface
{
    public function responder(string $pergunta, ?array $contexto = null, string $idioma = 'pt'): array
    {
        $sobre = $contexto === null || $contexto === []
            ? ''
            : ' sobre ' . implode(',', array_keys($contexto));

        return ['resposta' => "[assistente/{$idioma}{$sobre}] {$pergunta}"];
    }

    public function identificador(): string
    {
        return 'fake';
    }
}
