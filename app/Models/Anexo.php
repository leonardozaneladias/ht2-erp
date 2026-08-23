<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use HT2ML\Core\Models\Concerns\Auditavel;
use HT2ML\Core\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Anexo extends Model implements UsaSoftDeletes
{
    use Auditavel;
    use BelongsToEmpresa;
    use SoftDeletes;

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

    /**
     * O arquivo físico só é removido na exclusão definitiva (force-delete) ou no
     * GC — o soft-delete mantém o binário para retenção/auditoria (D4).
     */
    protected static function booted(): void
    {
        static::forceDeleted(function (self $anexo): void {
            Storage::disk($anexo->disco)->delete($anexo->caminho);
        });
    }
}
