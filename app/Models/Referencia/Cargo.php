<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * Cargo (CBO) — dado de referência global, editável (CBO + cargos próprios).
 *
 * @property int $id
 * @property string $codigo_cbo
 * @property string $descricao
 * @property bool $ativo
 */
class Cargo extends Model
{
    protected $table = 'cargos';

    /** @var list<string> */
    protected $fillable = ['codigo_cbo', 'descricao', 'ativo'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
