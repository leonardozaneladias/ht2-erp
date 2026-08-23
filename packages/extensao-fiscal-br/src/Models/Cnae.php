<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Models;

use App\Models\Concerns\Auditavel;
use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use HT2ML\FiscalBr\Database\Factories\CnaeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CNAE 2.3 (subclasse) — dado de referência global. Chave natural: codigo (7 díg).
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 * @property string|null $secao
 * @property string|null $divisao
 * @property string|null $grupo
 * @property string|null $classe
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property OrigemRegistro $origem
 */
class Cnae extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CnaeFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'cnaes';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao', 'secao', 'divisao', 'grupo', 'classe'];

    protected static function newFactory(): CnaeFactory
    {
        return CnaeFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['origem' => OrigemRegistro::class];
    }
}
