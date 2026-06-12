<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminUserDTO;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateAdminUserAction
{
    public function execute(AdminUser $usuario, AdminUserDTO $dto): AdminUser
    {
        return DB::transaction(function () use ($usuario, $dto): AdminUser {
            $atributos = [
                'nome' => $dto->nome,
                'email' => $dto->email,
                'ativo' => $dto->ativo,
                'telefone' => $dto->telefone,
                'cargo' => $dto->cargo,
            ];

            if ($dto->password !== null && $dto->password !== '') {
                $atributos['password'] = Hash::make($dto->password);
            }

            $usuario->update($atributos);
            $usuario->syncRoles($dto->roles);

            activity('admin_users')
                ->performedOn($usuario)
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'nome' => $usuario->nome,
                    'email' => $usuario->email,
                    'ativo' => $usuario->ativo,
                    'roles' => $usuario->getRoleNames()->all(),
                    'senha_alterada' => $dto->password !== null && $dto->password !== '',
                ])
                ->event('updated')
                ->log('Usuário admin atualizado');

            return $usuario->fresh(['roles']);
        });
    }
}
