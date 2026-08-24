<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ToggleAdminUserStatusAction
{
    public function execute(AdminUser $usuario): AdminUser
    {
        $causer = Auth::guard('admin')->user();

        if ($causer instanceof AdminUser && $causer->is($usuario)) {
            throw new RuntimeException('Você não pode desativar a si mesmo.');
        }

        return DB::transaction(function () use ($usuario): AdminUser {
            // A mudança de `ativo` (com antes/depois e causer) é capturada
            // automaticamente pelo trait Auditavel.
            $usuario->update(['ativo' => ! $usuario->ativo]);

            return $usuario->fresh(['roles']);
        });
    }
}
