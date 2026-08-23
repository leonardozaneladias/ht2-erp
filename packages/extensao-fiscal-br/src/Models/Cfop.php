<?php

declare(strict_types=1);

namespace HT2ML\FiscalBr\Models;

use HT2ML\Core\Enums\Referencia\OrigemRegistro;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\TemOrigem;
use HT2ML\Core\Models\Contracts\TemOrigemDeclarada;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use HT2ML\FiscalBr\Database\Factories\CfopFactory;
use HT2ML\FiscalBr\Enums\TipoCfop;
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
 * @property OrigemRegistro $origem
 */
class Cfop extends Model implements TemOrigemDeclarada, UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<CfopFactory> */
    use HasFactory;

    use SoftDeletes;
    use TemOrigem;

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
        return ['tipo' => TipoCfop::class, 'origem' => OrigemRegistro::class];
    }
}
