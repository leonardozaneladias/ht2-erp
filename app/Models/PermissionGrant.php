<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoConcessao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;

/**
 * @property int $id
 * @property int $admin_user_id
 * @property int $permission_id
 * @property TipoConcessao $type
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property string $reason
 * @property int|null $granted_by
 */
class PermissionGrant extends Model
{
    protected $fillable = [
        'admin_user_id',
        'permission_id',
        'type',
        'expires_at',
        'granted_by',
        'reason',
        'revoked_at',
        'contexto_tipo',
        'contexto_id',
    ];

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function concedidoPor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'granted_by');
    }

    /**
     * @return BelongsTo<Permission, $this>
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    /**
     * Concessões em vigor: não revogadas e não expiradas.
     *
     * @param  Builder<PermissionGrant>  $query
     */
    public function scopeVigentes(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where(static function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Concessões vencidas: com prazo já no passado e ainda não revogadas.
     *
     * @param  Builder<PermissionGrant>  $query
     */
    public function scopeVencidas(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * @param  Builder<PermissionGrant>  $query
     */
    public function scopeDoTipo(Builder $query, TipoConcessao $tipo): void
    {
        $query->where('type', $tipo->value);
    }

    public function estaVigente(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TipoConcessao::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
