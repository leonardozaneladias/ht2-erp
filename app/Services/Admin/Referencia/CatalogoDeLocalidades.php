<?php

declare(strict_types=1);

namespace App\Services\Admin\Referencia;

use App\Contracts\Referencia\FonteDeCargos;
use App\Contracts\Referencia\FonteDeMunicipios;
use App\Contracts\Referencia\FonteDeUnidadesFederativas;
use App\Models\Referencia\Cargo;
use App\Models\Referencia\Estado;
use App\Models\Referencia\Municipio;

/**
 * Implementação das fontes de catálogo sobre os models de referência.
 *
 * Vive no core, e fica: o ADR-0020 decidiu que `estados`, `municipios` e
 * `cargos` são de uso universal e não viram pacote de localização.
 */
final class CatalogoDeLocalidades implements FonteDeCargos, FonteDeMunicipios, FonteDeUnidadesFederativas
{
    /** @return array<string, string> */
    public function opcoes(): array
    {
        return Estado::query()->orderBy('nome')->pluck('nome', 'sigla')->all();
    }

    /** @return list<string> */
    public function daUnidadeFederativa(string $sigla): array
    {
        if ($sigla === '') {
            return [];
        }

        return Municipio::query()
            ->whereHas('estado', fn ($q) => $q->where('sigla', $sigla))
            ->orderBy('nome')
            ->pluck('nome')
            ->all();
    }

    /** @return list<string> */
    public function ativos(): array
    {
        return Cargo::query()->where('ativo', true)->orderBy('descricao')->pluck('descricao')->all();
    }
}
