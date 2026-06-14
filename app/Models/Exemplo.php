<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusExemplo;
use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property StatusExemplo $status
 * @property \Illuminate\Support\Carbon $data_inicio
 * @property \Illuminate\Support\Carbon|null $publicado_em
 */
class Exemplo extends Model
{
    use Auditavel;
    use BelongsToEmpresa;

    /** @use HasFactory<\Database\Factories\ExemploFactory> */
    use HasFactory;

    protected $table = 'exemplos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'slug',
        'site',
        'descricao',
        'email',
        'telefone',
        'cep',
        'cnpj',
        'cpf',
        'preco',
        'custo',
        'quantidade',
        'cor',
        'categoria',
        'tags',
        'destaque',
        'data_inicio',
        'publicado_em',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusExemplo::class,
            'preco' => 'integer',
            'custo' => 'decimal:2',
            'quantidade' => 'integer',
            'tags' => 'array',
            'destaque' => 'boolean',
            'data_inicio' => 'date',
            'publicado_em' => 'datetime',
        ];
    }
}
