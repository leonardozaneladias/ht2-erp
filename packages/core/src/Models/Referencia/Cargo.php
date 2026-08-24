<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Referencia;

use Database\Factories\Referencia\CargoFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cargo (CBO) — dado de referência global, editável (CBO + cargos próprios).
 *
 * @property int $id
 * @property string $codigo_cbo
 * @property string $descricao
 * @property bool $ativo
 * @property OrigemRegistro $origem
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Cargo extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CargoFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'cargos';

    /** @var list<string> */
    protected $fillable = ['codigo_cbo', 'descricao', 'ativo'];

    protected static function newFactory(): CargoFactory
    {
        return CargoFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'origem' => OrigemRegistro::class];
    }
}
