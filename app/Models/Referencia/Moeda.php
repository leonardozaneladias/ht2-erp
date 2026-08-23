<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use Database\Factories\Referencia\MoedaFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
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
 * @property OrigemRegistro $origem
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Moeda extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<MoedaFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

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
        return ['casas_decimais' => 'integer', 'ativo' => 'boolean', 'origem' => OrigemRegistro::class];
    }
}
