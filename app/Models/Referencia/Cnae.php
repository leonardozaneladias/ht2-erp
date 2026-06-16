<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\CnaeFactory;
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
 */
class Cnae extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CnaeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'cnaes';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao', 'secao', 'divisao', 'grupo', 'classe'];

    protected static function newFactory(): CnaeFactory
    {
        return CnaeFactory::new();
    }
}
