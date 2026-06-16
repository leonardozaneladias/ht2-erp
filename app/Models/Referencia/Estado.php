<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Enums\Referencia\RegiaoBrasil;
use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\EstadoFactory;
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
 */
class Estado extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<EstadoFactory> */
    use HasFactory;

    use SoftDeletes;

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
        return ['regiao' => RegiaoBrasil::class];
    }
}
