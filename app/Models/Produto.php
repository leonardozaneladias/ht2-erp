<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusProduto;
use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property StatusProduto $status
 */
class Produto extends Model
{
    use Auditavel;
    use BelongsToEmpresa;

    /** @use HasFactory<\Database\Factories\ProdutoFactory> */
    use HasFactory;

    protected $table = 'produtos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'sku',
        'preco',
        'descricao',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusProduto::class,
            'preco' => 'integer',
        ];
    }
}
