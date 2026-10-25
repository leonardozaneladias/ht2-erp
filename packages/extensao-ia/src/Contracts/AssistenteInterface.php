<?php

declare(strict_types=1);

namespace HT2ML\Ia\Contracts;

/**
 * Assistente conversacional.
 *
 * O contexto é um mapa livre, montado pelo produto: o que é relevante para uma
 * pergunta (o aluno em foco, o pedido aberto, o cliente) é de domínio, e o
 * contrato não deveria saber. A implementação real decide como transformar o
 * mapa em instrução para o modelo.
 */
interface AssistenteInterface
{
    /**
     * @param  array<string, mixed>|null  $contexto
     * @param  string  $idioma  código de duas letras em que responder
     * @return array{resposta: string}
     */
    public function responder(string $pergunta, ?array $contexto = null, string $idioma = 'pt'): array;

    /** Ver TradutorInterface::identificador(). */
    public function identificador(): string;
}
