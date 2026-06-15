<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Models\Contracts\UsaSoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Empresa extends Model implements UsaSoftDeletes
{
    use Auditavel;

    /** @use HasFactory<\Database\Factories\EmpresaFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'razao_social',
        'cnpj',
        'inscricao_estadual',
        'telefone',
        'email',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'site_url',
        'logo_arquivo',
        'logo_dark_arquivo',
        'favicon_arquivo',
        'cor_primaria',
        'cor_secundaria',
        'cor_sucesso',
        'cor_warning',
        'cor_perigo',
        'cor_info',
        'ativo',
    ];

    /**
     * @return HasMany<Filial, $this>
     */
    public function filiais(): HasMany
    {
        return $this->hasMany(Filial::class);
    }

    /**
     * @return BelongsToMany<AdminUser, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_empresa')
            ->withPivot('todas_filiais')
            ->withTimestamps();
    }

    /**
     * @param  Builder<Empresa>  $query
     * @return Builder<Empresa>
     */
    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    /**
     * CNPJ com máscara (XX.XXX.XXX/XXXX-XX). Mantém o valor original quando não
     * houver 14 dígitos — entrada parcial/legada não é mascarada à força.
     *
     * @return Attribute<string|null, never>
     */
    protected function cnpjFormatado(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $cnpj = $this->cnpj;

                if ($cnpj === null || $cnpj === '') {
                    return null;
                }

                $digitos = preg_replace('/\D/', '', $cnpj) ?? '';

                if (strlen($digitos) !== 14) {
                    return $cnpj;
                }

                return preg_replace(
                    '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
                    '$1.$2.$3/$4-$5',
                    $digitos,
                );
            },
        );
    }
}
