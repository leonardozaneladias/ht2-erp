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

            $senhaAlterada = $dto->password !== null && $dto->password !== '';

            if ($senhaAlterada) {
                $atributos['password'] = Hash::make((string) $dto->password);
            }

            // O updated com o diff vem do trait Auditavel; aqui só os eventos
            // de domínio que o diff não expressa (senha excluída por segurança
            // e pivot de roles).
            $rolesAntes = $usuario->getRoleNames()->sort()->values()->all();

            $usuario->update($atributos);
            $usuario->syncRoles($dto->roles);

            $causer = Auth::guard('admin')->user();

            if ($senhaAlterada) {
                activity('admin_users')
                    ->performedOn($usuario)
                    ->causedBy($causer)
                    ->event('senha_alterada')
                    ->log('Senha do usuário alterada');
            }

            $rolesDepois = $usuario->getRoleNames()->sort()->values()->all();

            if ($rolesAntes !== $rolesDepois) {
                activity('admin_users')
                    ->performedOn($usuario)
                    ->causedBy($causer)
                    ->withProperties(['roles' => $rolesDepois])
                    ->event('perfis_sincronizados')
                    ->log('Perfis do usuário atualizados');
            }

            return $usuario->fresh(['roles']);
        });
    }
}
