<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class AdminUser extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = [
        'nome',
        'email',
        'password',
        'avatar_url',
        'ativo',
        'bloqueado_ate',
        'last_login_at',
        'last_login_ip',
        'perfil_ativo_role_id',
        'empresa_ativa_id',
        'filial_ativa_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nome', 'email', 'ativo'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('admin_users');
    }

    /**
     * @return HasMany<PermissionGrant, $this>
     */
    public function permissionGrants(): HasMany
    {
        return $this->hasMany(PermissionGrant::class, 'admin_user_id');
    }

    /**
     * Perfil (role) ativo opcional — lente de atuação do usuário.
     *
     * @return BelongsTo<Role, $this>
     */
    public function perfilAtivo(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'perfil_ativo_role_id');
    }

    /**
     * Empresas a que o usuário tem acesso (pivot indica se inclui todas as filiais).
     *
     * @return BelongsToMany<Empresa, $this>
     */
    public function empresasAcessiveis(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'admin_user_empresa')
            ->withPivot('todas_filiais')
            ->withTimestamps();
    }

    /**
     * Filiais específicas concedidas ao usuário (quando o acesso não é "todas").
     *
     * @return BelongsToMany<Filial, $this>
     */
    public function filiaisAcessiveis(): BelongsToMany
    {
        return $this->belongsToMany(Filial::class, 'admin_user_filial')->withTimestamps();
    }

    /**
     * @return BelongsTo<Empresa, $this>
     */
    public function empresaAtiva(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_ativa_id');
    }

    /**
     * @return BelongsTo<Filial, $this>
     */
    public function filialAtiva(): BelongsTo
    {
        return $this->belongsTo(Filial::class, 'filial_ativa_id');
    }

    /**
     * Papéis atribuídos ao usuário no escopo de empresas (dimensão por empresa).
     * Complementa os papéis globais de `roles()` (spatie), que valem em todas.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function papeisPorEmpresa(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_user_empresa_role')
            ->withPivot('empresa_id')
            ->withTimestamps();
    }

    /**
     * Papéis do usuário em uma empresa específica (apenas a dimensão por empresa).
     *
     * @return Collection<int, Role>
     */
    public function rolesNaEmpresa(int $empresaId): Collection
    {
        return $this->papeisPorEmpresa()->wherePivot('empresa_id', $empresaId)->get();
    }

    public function temAcessoAEmpresa(int $empresaId): bool
    {
        return $this->empresasAcessiveis()->whereKey($empresaId)->exists();
    }

    public function temAcessoAFilial(int $filialId): bool
    {
        $filial = Filial::query()->find($filialId);

        if ($filial === null) {
            return false;
        }

        $temTodasDaEmpresa = $this->empresasAcessiveis()
            ->where('empresas.id', $filial->empresa_id)
            ->wherePivot('todas_filiais', true)
            ->exists();

        if ($temTodasDaEmpresa) {
            return true;
        }

        return $this->filiaisAcessiveis()->whereKey($filialId)->exists();
    }

    /**
     * 2FA está ativo quando há um segredo confirmado.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * A conta está temporariamente bloqueada por excesso de falhas de login.
     */
    public function estaBloqueada(): bool
    {
        return $this->bloqueado_ate !== null && $this->bloqueado_ate->isFuture();
    }

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'bloqueado_ate' => 'datetime',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
