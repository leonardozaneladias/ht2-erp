<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\AdminUser;
use App\Models\PermissionGrant;
use App\Services\Admin\AccessResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevogarAcessoDiretoAction
{
    public function __construct(private readonly AccessResolver $resolver) {}

    public function execute(PermissionGrant $grant, string $motivo, ?AdminUser $ator = null): void
    {
        if (! $ator instanceof AdminUser) {
            $atual = Auth::guard('admin')->user();
            $ator = $atual instanceof AdminUser ? $atual : null;
        }

        DB::transaction(function () use ($grant, $motivo, $ator): void {
            $alvo = AdminUser::findOrFail($grant->admin_user_id);

            $grant->update(['revoked_at' => now()]);

            activity('acessos')
                ->performedOn($alvo)
                ->causedBy($ator)
                ->withProperties([
                    'permissao' => $grant->permission?->name,
                    'tipo' => $grant->type->value,
                    'motivo' => $motivo,
                ])
                ->event('acesso_revogado')
                ->log('Acesso direto revogado');

            $this->resolver->invalidar($alvo);
        });
    }
}
