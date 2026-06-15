<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Anexo extends Model
{
    use Auditavel;
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'disco',
        'caminho',
        'nome_original',
        'mime',
        'tamanho',
    ];

    /** @return MorphTo<Model, $this> */
    public function anexavel(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk($this->disco)->url($this->caminho);
    }

    public function tamanhoFormatado(): string
    {
        $bytes = (int) $this->tamanho;

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    protected function casts(): array
    {
        return [
            'tamanho' => 'integer',
        ];
    }
}
