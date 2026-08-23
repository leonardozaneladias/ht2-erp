<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Enums\Referencia\OrigemRegistro;
use App\Models\Concerns\Auditavel;
use App\Models\Concerns\TemOrigem;
use App\Models\Contracts\TemOrigemDeclarada;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\NcmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * NCM — dado de referência global. Chave natural: codigo (8 díg).
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property OrigemRegistro $origem
 */
class Ncm extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<NcmFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

    protected $table = 'ncms';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao'];

    protected static function newFactory(): NcmFactory
    {
        return NcmFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['origem' => OrigemRegistro::class];
    }
}
