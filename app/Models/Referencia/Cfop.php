<?php

declare(strict_types=1);

namespace App\Models\Referencia;

use App\Enums\Referencia\TipoCfop;
use Illuminate\Database\Eloquent\Model;

/**
 * CFOP — dado de referência global. Chave natural: codigo (4 díg). O `tipo`
 * (entrada/saída) é derivado do 1º dígito na seed.
 *
 * @property int $id
 * @property string $codigo
 * @property string $descricao
 * @property TipoCfop $tipo
 * @property string|null $aplicacao
 */
class Cfop extends Model
{
    protected $table = 'cfops';

    /** @var list<string> */
    protected $fillable = ['codigo', 'descricao', 'tipo', 'aplicacao'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['tipo' => TipoCfop::class];
    }
}
