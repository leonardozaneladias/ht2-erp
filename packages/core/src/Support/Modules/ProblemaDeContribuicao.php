<?php

declare(strict_types=1);

namespace HT2ML\Core\Support\Modules;

/**
 * Uma contribuição de módulo que não pôde ser aplicada.
 *
 * O diagnóstico precisa dizer TRÊS coisas para ser acionável — qual canal, qual
 * alvo, e quem declarou. Sem a origem, "seção de menu inexistente: 'escola'"
 * manda o desenvolvedor procurar em todos os pacotes instalados; com ela, o
 * arquivo e a linha estão na mensagem.
 */
final readonly class ProblemaDeContribuicao
{
    public function __construct(
        /** 'area', 'permissoes' ou 'menu' */
        public string $canal,
        /** A chave que falhou — a área, a seção, o item. */
        public string $alvo,
        public string $mensagem,
        /** Arquivo:linha da declaração, quando capturável. */
        public ?string $origem = null,
    ) {}

    public function __toString(): string
    {
        return sprintf(
            '[%s] %s%s',
            $this->canal,
            $this->mensagem,
            $this->origem === null ? '' : " (declarado em {$this->origem})",
        );
    }
}
