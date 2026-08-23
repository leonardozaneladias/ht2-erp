<?php

declare(strict_types=1);

namespace App\Models;

use HT2ML\Core\Models\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sequência numérica de documentos por empresa/tipo/ano.
 * Modelo de infraestrutura — sem BelongsToEmpresa (o global scope quebraria
 * o lockForUpdate em transações concorrentes) e sem Auditavel (não é entidade
 * de negócio; é contador técnico append-only).
 *
 * @property int $id
 * @property int $empresa_id
 * @property string $tipo
 * @property int $ano
 * @property int $ultimo_numero
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DocumentSequence extends Model
{
    protected $table = 'document_sequences';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'tipo',
        'ano',
        'ultimo_numero',
    ];

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'empresa_id' => 'integer',
            'ano' => 'integer',
            'ultimo_numero' => 'integer',
        ];
    }
}
