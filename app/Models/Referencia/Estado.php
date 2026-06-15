<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Enums\Referencia\RegiaoBrasil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estado (UF) — dado de referência global. Chave natural: codigo_ibge (2 díg).
 *
 * @property int $id
 * @property string $codigo_ibge
 * @property string $sigla
 * @property string $nome
 * @property RegiaoBrasil $regiao
 */
class Estado extends Model
{
    protected $table = 'estados';

    /** @var list<string> */
    protected $fillable = ['codigo_ibge', 'sigla', 'nome', 'regiao'];

    /** @return HasMany<Municipio, $this> */
    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['regiao' => RegiaoBrasil::class];
    }
}
