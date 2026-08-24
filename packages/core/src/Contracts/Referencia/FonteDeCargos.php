<?php

declare(strict_types=1);

namespace HT2ML\Core\Contracts\Referencia;

/**
 * Fornece o catálogo de cargos para os formulários do core.
 *
 * Ver FonteDeUnidadesFederativas para o desenho, o motivo da indireção e o
 * aviso sobre app()->bound().
 */
interface FonteDeCargos
{
    /**
     * Descrições dos cargos ativos, ordenadas.
     *
     * @return list<string>
     */
    public function ativos(): array;
}
