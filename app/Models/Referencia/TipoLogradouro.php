<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\TipoLogradouroFactory;
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
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class TipoLogradouro extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<TipoLogradouroFactory> */
    use HasFactory;

    use SoftDeletes;

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
        return ['ativo' => 'boolean'];
    }
}
