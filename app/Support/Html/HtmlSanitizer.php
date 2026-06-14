<?php

declare(strict_types=1);

namespace App\Support\Html;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitiza HTML vindo de editores rich text (Quill) antes de persistir e/ou
 * exibir. Allowlist mínima: apenas formatação inline e listas/links — nada de
 * scripts, estilos, imagens ou atributos de evento. É idempotente (limpar duas
 * vezes não altera o resultado), então pode rodar na entrada e na exibição.
 */
final class HtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier instanceof HTMLPurifier) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,ul,ol,li,a[href]');
        // Sem cache em disco (evita exigir diretório gravável no deploy).
        $config->set('Cache.DefinitionImpl', null);
        // Links abrem em nova aba com rel seguro.
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);

        return self::$purifier = new HTMLPurifier($config);
    }
}
