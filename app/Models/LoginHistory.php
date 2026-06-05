<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico de logins de um AdminUser (append-only, sem updated_at).
 *
 * @property int $admin_user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class LoginHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'admin_login_history';

    protected $fillable = [
        'admin_user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
