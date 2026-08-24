<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Referencia;

use Database\Factories\Referencia\EstadoFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Enums\Referencia\RegiaoBrasil;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Estado (UF) — dado de referência global. Chave natural: codigo_ibge (2 díg).
 *
 * @property int $id
 * @property string $codigo_ibge
 * @property string $sigla
 * @property string $nome
 * @property RegiaoBrasil $regiao
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property OrigemRegistro $origem
 */
class Estado extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<EstadoFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'estados';

    /** @var list<string> */
    protected $fillable = ['codigo_ibge', 'sigla', 'nome', 'regiao'];

    /** @return HasMany<Municipio, $this> */
    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    protected static function newFactory(): EstadoFactory
    {
        return EstadoFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['regiao' => RegiaoBrasil::class, 'origem' => OrigemRegistro::class];
    }
}
