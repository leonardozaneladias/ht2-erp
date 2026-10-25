<?php

declare(strict_types=1);

namespace HT2ML\Ia\Contracts;

/** Provedor de resumo de texto. */
interface ResumidorInterface
{
    /**
     * @param  string  $idioma  código de duas letras (`pt`, `en`, `es`) em que o resumo deve sair
     * @return array{texto: string}
     */
    public function resumir(string $titulo, string $corpo, string $idioma = 'pt'): array;

    /** Ver TradutorInterface::identificador(). */
    public function identificador(): string;
}
