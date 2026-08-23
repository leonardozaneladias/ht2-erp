<?php

declare(strict_types=1);

namespace App\Contracts\Referencia;

/**
 * Fornece o catálogo de cargos para os formulários do core.
 *
 * Ver FonteDeUnidadesFederativas para o desenho e o motivo da indireção.
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
