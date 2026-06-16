<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Enums\Referencia\TipoCfop;
use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Database\Factories\Referencia\CfopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CFOP — dado de referência global. Chave natural: codigo (4 díg). O `tipo`
 * (entrada/saída) é derivado do 1º dígito na seed.
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 * @property TipoCfop $tipo
 * @property string|null $aplicacao
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Cfop extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CfopFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'cfops';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao', 'tipo', 'aplicacao'];

    protected static function newFactory(): CfopFactory
    {
        return CfopFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['tipo' => TipoCfop::class];
    }
}
