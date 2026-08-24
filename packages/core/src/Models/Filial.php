<?php

declare(strict_types=1);

namespace HT2ML\Core\Models;

use HT2ML\Core\Database\Factories\FilialFactory;
use HT2ML\Core\Models\Concerns\Auditavel;
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

    /** @use HasFactory<\HT2ML\Core\Database\Factories\FilialFactory> */
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

    /**
     * O model saiu de App\ para o pacote, e o resolvedor padrão do Laravel monta
     * o nome da factory a partir do namespace da aplicação — para uma classe
     * fora dele, erra o alvo. Declarar explicitamente é o mesmo padrão que os
     * models de referência já usam.
     */
    protected static function newFactory(): FilialFactory
    {
        return FilialFactory::new();
    }

    protected function casts(): array
    {
        return [
            'e_matriz' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
