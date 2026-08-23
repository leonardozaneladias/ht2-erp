<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Contracts;

use HT2ML\Core\Enums\Referencia\OrigemRegistro;

/**
 * Contrato que marca um model usando o trait HT2ML\Core\Models\Concerns\TemOrigem.
 *
 * Não adiciona comportamento — o trait já implementa estes métodos. A interface
 * só os torna visíveis ao type-system quando o model é manipulado de forma
 * genérica, como nas guardas de escrita (ProtegeRegistroSincronizado e
 * ComLixeira), que recebem um Model qualquer e precisam perguntar pela origem.
 */
interface TemOrigemDeclarada
{
    /** Linha mantida pela fonte oficial — somente leitura. */
    public function sincronizado(): bool;

    /** Linha criada nesta instalação — editável, e intocada pelo sync. */
    public function cadastradoAqui(): bool;

    public function getOrigem(): OrigemRegistro;
}
