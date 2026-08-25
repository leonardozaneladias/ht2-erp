<?php

declare(strict_types=1);

namespace HT2ML\Core\Exceptions;

use HT2ML\Core\Support\Modules\ProblemaDeContribuicao;
use RuntimeException;

/**
 * Uma ou mais contribuições de módulo foram descartadas na aplicação.
 *
 * Lançada FORA de produção, na aplicação e não na declaração. A distinção é a
 * causa raiz que este desenho corrige: `permissoes()` validava no ato da
 * declaração e lançava, enquanto `itensDeMenu()` não validava e a seção
 * inexistente era descartada por um `continue` silencioso. Os dois estavam
 * errados pelo mesmo motivo — no ato da declaração a config de outra extensão
 * pode ainda não ter sido mesclada, então validar cedo produz falso positivo, e
 * descartar sem avisar produz tela que não existe sem ninguém saber por quê.
 *
 * Em produção não lança: um deploy de sexta-feira não vira tela branca por causa
 * de uma seção de menu com o nome trocado. Lá o registro é Log::error e a
 * contribuição inválida é pulada — o mesmo efeito de antes, agora audível.
 */
final class ContribuicoesInvalidas extends RuntimeException
{
    /**
     * @param  list<ProblemaDeContribuicao>  $problemas
     */
    public function __construct(public readonly array $problemas)
    {
        parent::__construct(sprintf(
            "%d contribuição(ões) de módulo foram descartadas:\n  - %s\n\nRode `php artisan ht2ml:doutor` para o diagnóstico completo.",
            count($problemas),
            implode("\n  - ", array_map(strval(...), $problemas)),
        ));
    }
}
