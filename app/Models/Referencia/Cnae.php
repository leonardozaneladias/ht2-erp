<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;

/**
 * CNAE 2.3 (subclasse) — dado de referência global. Chave natural: codigo (7 díg).
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 * @property string|null $secao
 * @property string|null $divisao
 * @property string|null $grupo
 * @property string|null $classe
 */
class Cnae extends Model
{
    protected $table = 'cnaes';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao', 'secao', 'divisao', 'grupo', 'classe'];
}
