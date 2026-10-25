<?php

declare(strict_types=1);

namespace HT2ML\Ia\Contracts;

/**
 * Provedor de tradução. O produto liga a implementação real (Anthropic, por
 * exemplo) trocando o binding; o resto do código não muda.
 */
interface TradutorInterface
{
    /**
     * @return array{texto: string}
     */
    public function traduzir(string $texto, string $idiomaOrigem, string $idiomaDestino): array;

    /**
     * Nome do provedor que atendeu a chamada, para a coluna de proveniência.
     *
     * Existe porque "qual modelo escreveu isto?" é pergunta de auditoria, e uma
     * coluna com `default('fake')` na migration responderia `fake` em silêncio
     * no dia em que um provedor real fosse ligado. Cada implementação declara o
     * próprio nome; nada aqui escolhe provedor.
     */
    public function identificador(): string;
}
