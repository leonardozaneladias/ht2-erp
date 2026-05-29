<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\AdminUser;
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

        return DB::transaction(function () use ($usuario, $causer): AdminUser {
            $usuario->update(['ativo' => ! $usuario->ativo]);

            activity('admin_users')
                ->performedOn($usuario)
                ->causedBy($causer)
                ->withProperties(['ativo' => $usuario->ativo])
                ->event($usuario->ativo ? 'reativado' : 'desativado')
                ->log($usuario->ativo ? 'Usuário admin reativado' : 'Usuário admin desativado');

            return $usuario->fresh(['roles']);
        });
    }
}
