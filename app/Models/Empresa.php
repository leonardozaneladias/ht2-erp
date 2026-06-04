<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    /** @use HasFactory<\Database\Factories\EmpresaFactory> */
    use HasFactory;

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
}
