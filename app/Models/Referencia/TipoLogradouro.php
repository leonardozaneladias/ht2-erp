<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de logradouro — dado de referência global. Chave natural: nome.
 *
 * @property int $id
 * @property string $nome
 * @property string|null $codigo
 * @property string|null $abrev
 * @property bool $ativo
 */
class TipoLogradouro extends Model
{
    protected $table = 'tipos_logradouro';

    /** @var list<string> */
    protected $fillable = ['nome', 'codigo', 'abrev', 'ativo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
