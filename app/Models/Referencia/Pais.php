<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * País — dado de referência global. Chave natural: codigo_iso2.
 *
 * @property int $id
 * @property string $codigo_iso2
 * @property string|null $codigo_iso3
 * @property string|null $codigo_numerico
 * @property string $nome
 * @property bool $ativo
 */
class Pais extends Model
{
    protected $table = 'paises';

    /** @var list<string> */
    protected $fillable = ['codigo_iso2', 'codigo_iso3', 'codigo_numerico', 'nome', 'ativo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
