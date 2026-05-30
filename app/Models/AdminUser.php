<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        'last_login_at',
        'last_login_ip',
        'perfil_ativo_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
