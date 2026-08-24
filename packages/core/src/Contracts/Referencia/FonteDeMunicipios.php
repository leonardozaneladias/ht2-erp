<?php

declare(strict_types=1);

namespace HT2ML\Core\Contracts\Referencia;

/**
 * Fornece os municípios de uma UF para os formulários do core.
 *
 * Ver FonteDeUnidadesFederativas para o desenho, o motivo da indireção e o
 * aviso sobre app()->bound().
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
