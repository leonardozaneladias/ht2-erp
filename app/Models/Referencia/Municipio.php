<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Município IBGE — dado de referência global. Chave natural: codigo_ibge (7 díg).
 *
 * @property int $id
 * @property string $codigo_ibge
 * @property string $nome
 * @property int $estado_id
 */
class Municipio extends Model
{
    protected $table = 'municipios';

    /** @var list<string> */
    protected $fillable = ['codigo_ibge', 'nome', 'estado_id'];

    /** @return BelongsTo<Estado, $this> */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }
}
