<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
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
 */
class Ncm extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<NcmFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'ncms';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao'];

    protected static function newFactory(): NcmFactory
    {
        return NcmFactory::new();
    }
}
