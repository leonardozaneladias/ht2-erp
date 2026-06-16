<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\CargoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cargo (CBO) — dado de referência global, editável (CBO + cargos próprios).
 *
 * @property int $id
 * @property string $codigo_cbo
 * @property string $descricao
 * @property bool $ativo
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Cargo extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CargoFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'cargos';

    /** @var list<string> */
    protected $fillable = ['codigo_cbo', 'descricao', 'ativo'];

    protected static function newFactory(): CargoFactory
    {
        return CargoFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
