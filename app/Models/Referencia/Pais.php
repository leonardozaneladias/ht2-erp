<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\PaisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * País — dado de referência global. Chave natural: codigo_iso2.
 *
 * @property int $id
 * @property string $codigo_iso2
 * @property string|null $codigo_iso3
 * @property string|null $codigo_numerico
 * @property string $nome
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Pais extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<PaisFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'paises';

    /** @var list<string> */
    protected $fillable = ['codigo_iso2', 'codigo_iso3', 'codigo_numerico', 'nome', 'ativo'];

    protected static function newFactory(): PaisFactory
    {
        return PaisFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
