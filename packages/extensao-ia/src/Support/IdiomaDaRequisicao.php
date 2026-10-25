<?php

declare(strict_types=1);

namespace HT2ML\Ia\Support;

/**
 * O idioma efetivo da requisição, como código de duas letras.
 *
 * Fonte única: quem resolve a precedência (header, preferência do usuário,
 * default) é o middleware de idioma do produto — na extensao-api, o
 * DefinirIdioma —, que chama app()->setLocale(). O locale ativo É o idioma
 * efetivo; buscar de outro lugar criaria uma segunda verdade.
 *
 * A conversão existe porque o locale do Laravel é `pt_BR`, e um provedor de IA
 * espera um código de idioma (`pt`) numa instrução do tipo "responda em ...".
 */
final class IdiomaDaRequisicao
{
    public static function codigo(): string
    {
        return substr(app()->getLocale(), 0, 2);
    }
}
