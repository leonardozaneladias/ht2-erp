<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DefinirPerfilAtivoAction
{
    public function __construct(private readonly AccessResolver $resolver) {}

    /**
     * Define o perfil ativo (lente) do usuário. $roleId nulo = "ver tudo".
     */
    public function execute(AdminUser $user, ?int $roleId): AdminUser
    {
        $user->loadMissing('roles');

        if ($roleId !== null && ! $user->roles->contains('id', $roleId)) {
            $roleId = null;
        }

        return DB::transaction(function () use ($user, $roleId): AdminUser {
            $user->update(['perfil_ativo_role_id' => $roleId]);
            Session::put('admin.perfil_ativo_role_id', $roleId);

            $this->resolver->invalidar($user);

            return $user->fresh(['roles', 'perfilAtivo']);
        });
    }
}
