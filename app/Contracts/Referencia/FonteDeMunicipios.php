<?php

declare(strict_types=1);

namespace App\Contracts\Referencia;

/**
 * Fornece os municípios de uma UF para os formulários do core.
 *
 * Ver FonteDeUnidadesFederativas para o desenho e o motivo da indireção.
 */
interface FonteDeMunicipios
{
    /**
     * Nomes dos municípios da UF, ordenados. Lista vazia se a UF não existir.
     *
     * @return list<string>
     */
    public function daUnidadeFederativa(string $sigla): array;
}
