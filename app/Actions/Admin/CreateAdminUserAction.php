<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminUserDTO;
use HT2ML\Core\Models\AdminUser;
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
                'telefone' => $dto->telefone,
                'cargo' => $dto->cargo,
            ]);

            // O created com o diff completo vem do trait Auditavel; aqui só o
            // evento de domínio que o diff não captura (pivot de roles).
            if ($dto->roles !== []) {
                $usuario->syncRoles($dto->roles);

                activity('admin_users')
                    ->performedOn($usuario)
                    ->causedBy(Auth::guard('admin')->user())
                    ->withProperties(['roles' => $usuario->getRoleNames()->all()])
                    ->event('perfis_sincronizados')
                    ->log('Perfis do usuário atualizados');
            }

            return $usuario->fresh(['roles']);
        });
    }
}
