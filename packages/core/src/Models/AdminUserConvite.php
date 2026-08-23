<?php

declare(strict_types=1);

namespace HT2ML\Core\Models;

use HT2ML\Core\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convite de acesso de um usuário admin: o convidado define a própria senha e,
 * ao aceitar, o e-mail é considerado verificado. Apenas o hash do token é
 * persistido — o token em claro vai só no e-mail.
 *
 * @property \Illuminate\Support\Carbon $expira_em
 * @property \Illuminate\Support\Carbon|null $aceito_em
 */
class AdminUserConvite extends Model
{
    use Auditavel;

    protected $table = 'admin_user_convites';

    protected $fillable = [
        'admin_user_id',
        'token_hash',
        'expira_em',
        'aceito_em',
        'enviado_por',
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
    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'enviado_por');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendentes(Builder $query): Builder
    {
        return $query->whereNull('aceito_em')->where('expira_em', '>', now());
    }

    public function estaExpirado(): bool
    {
        return $this->expira_em->isPast();
    }

    public function foiAceito(): bool
    {
        return $this->aceito_em !== null;
    }

    public function estaUtilizavel(): bool
    {
        return ! $this->foiAceito() && ! $this->estaExpirado();
    }

    public static function localizarPorToken(string $token): ?self
    {
        return self::query()->where('token_hash', hash('sha256', $token))->first();
    }

    /**
     * O hash do token é segredo de autenticação — nunca entra no diff.
     *
     * @return list<string>
     */
    protected function atributosNaoAuditados(): array
    {
        return ['token_hash'];
    }

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'aceito_em' => 'datetime',
        ];
    }
}
