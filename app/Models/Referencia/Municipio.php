<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use Database\Factories\Referencia\MunicipioFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Município IBGE — dado de referência global. Chave natural: codigo_ibge (7 díg).
 *
 * @property int $id
 * @property string $codigo_ibge
 * @property string $nome
 * @property int $estado_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property OrigemRegistro $origem
 */
class Municipio extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<MunicipioFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'municipios';

    /** @var list<string> */
    protected $fillable = ['codigo_ibge', 'nome', 'estado_id'];

    /** @return BelongsTo<Estado, $this> */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    protected static function newFactory(): MunicipioFactory
    {
        return MunicipioFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['origem' => OrigemRegistro::class];
    }
}
