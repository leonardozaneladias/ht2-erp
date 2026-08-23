<?php

declare(strict_types=1);

namespace HT2ML\Rh\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToEmpresa;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use HT2ML\Rh\Database\Factories\DepartamentoFactory;
use HT2ML\Rh\Enums\StatusDepartamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property StatusDepartamento $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Departamento extends Model implements UsaSoftDeletes
{
    use Auditavel;
    use BelongsToEmpresa;

    /** @use HasFactory<\HT2ML\Rh\Database\Factories\DepartamentoFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'departamentos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'sigla',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusDepartamento::class,
        ];
    }

    /** Models de pacote precisam apontar a factory (o resolver padrão assume App\). */
    protected static function newFactory(): DepartamentoFactory
    {
        return DepartamentoFactory::new();
    }
}
