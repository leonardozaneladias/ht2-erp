<?php

declare(strict_types=1);

namespace HT2ML\Core\Models\Referencia;

use Database\Factories\Referencia\TipoLogradouroFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tipo de logradouro — dado de referência global. Chave natural: nome.
 *
 * @property int $id
 * @property string $nome
 * @property string|null $codigo
 * @property string|null $abrev
 * @property bool $ativo
 * @property OrigemRegistro $origem
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TipoLogradouro extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<TipoLogradouroFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'tipos_logradouro';

    /** @var list<string> */
    protected $fillable = ['nome', 'codigo', 'abrev', 'ativo'];

    protected static function newFactory(): TipoLogradouroFactory
    {
        return TipoLogradouroFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'origem' => OrigemRegistro::class];
    }
}
