<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Filial extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<\Database\Factories\FilialFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'filiais';

    protected $fillable = [
        'empresa_id',
        'nome',
        'cnpj',
        'inscricao_estadual',
        'telefone',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'e_matriz',
        'ativo',
    ];

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    protected function casts(): array
    {
        return [
            'e_matriz' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
