<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminUserDTO;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserAction
{
    public function execute(AdminUserDTO $dto): AdminUser
    {
        return DB::transaction(function () use ($dto): AdminUser {
            $usuario = AdminUser::create([
                'nome' => $dto->nome,
                'email' => $dto->email,
                'password' => Hash::make((string) $dto->password),
                'ativo' => $dto->ativo,
            ]);

            if ($dto->roles !== []) {
                $usuario->syncRoles($dto->roles);
            }

            activity('admin_users')
                ->performedOn($usuario)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'ativo' => $usuario->ativo,
                    'roles' => $usuario->getRoleNames()->all(),
                ])
                ->event('created')
                ->log('Usuário admin criado');

            return $usuario->fresh(['roles']);
        });
    }
}
