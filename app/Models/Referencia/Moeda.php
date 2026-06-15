<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * Moeda (ISO 4217) — dado de referência global. Chave natural: codigo_iso.
 *
 * @property int $id
 * @property string $codigo_iso
 * @property string|null $numerico
 * @property string $nome
 * @property string|null $simbolo
 * @property int $casas_decimais
 * @property bool $ativo
 */
class Moeda extends Model
{
    protected $table = 'moedas';

    /** @var list<string> */
    protected $fillable = ['codigo_iso', 'numerico', 'nome', 'simbolo', 'casas_decimais', 'ativo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['casas_decimais' => 'integer', 'ativo' => 'boolean'];
    }
}
