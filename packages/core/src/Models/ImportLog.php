<?php

declare(strict_types=1);

namespace HT2ML\Core\Models;

use HT2ML\Core\Enums\StatusImport;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use Auditavel;
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'tipo',
        'arquivo_original',
        'total_linhas',
        'linhas_importadas',
        'linhas_com_erro',
        'status',
        'erros',
    ];

    protected function casts(): array
    {
        return [
            'total_linhas' => 'integer',
            'linhas_importadas' => 'integer',
            'linhas_com_erro' => 'integer',
            'status' => StatusImport::class,
            'erros' => 'array',
        ];
    }
}
