<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\MoedaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Moeda (ISO 4217) — dado de referência global. Chave natural: codigo_iso.
 *
 * @property int $id
 * @property string $codigo_iso
 * @property string|null $numerico
 * @property string $nome
 * @property string|null $simbolo
 * @property int $casas_decimais
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Moeda extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<MoedaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'moedas';

    /** @var list<string> */
    protected $fillable = ['codigo_iso', 'numerico', 'nome', 'simbolo', 'casas_decimais', 'ativo'];

    protected static function newFactory(): MoedaFactory
    {
        return MoedaFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['casas_decimais' => 'integer', 'ativo' => 'boolean'];
    }
}
