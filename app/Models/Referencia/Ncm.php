<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * NCM — dado de referência global. Chave natural: codigo (8 díg).
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 */
class Ncm extends Model
{
    protected $table = 'ncms';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao'];
}
