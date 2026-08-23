<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use Database\Factories\Referencia\BancoFactory;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Banco (participante do SPB) — dado de referência global. Chave natural: ispb.
 *
 * @property int $id
 * @property string $ispb
 * @property string|null $codigo_compe
 * @property string $nome
 * @property string|null $nome_completo
 * @property bool $ativo
 * @property OrigemRegistro $origem
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Banco extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<BancoFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'bancos';

    /** @var list<string> */
    protected $fillable = ['ispb', 'codigo_compe', 'nome', 'nome_completo', 'ativo'];

    protected static function newFactory(): BancoFactory
    {
        return BancoFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'origem' => OrigemRegistro::class];
    }
}
