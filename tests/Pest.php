<?php

declare(strict_types=1);

use App\Enums\TipoConcessao;
use App\Models\AdminUser;
use App\Models\PermissionGrant;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/**
 * Cria um AdminUser de teste.
 */
function criarAdminUser(string $email = 'user@teste.com', bool $ativo = true): AdminUser
{
    return AdminUser::create([
        'nome' => 'Usuário Teste',
        'email' => $email,
        'password' => Hash::make('password'),
        'ativo' => $ativo,
    ]);
}

/**
 * Cria uma concessão ou negação direta de permissão para um usuário (guard admin).
 */
function concederAcessoDireto(
    AdminUser $user,
    string $permissao,
    TipoConcessao $tipo,
    ?string $expiraEm = null,
): PermissionGrant {
    $permission = Permission::query()
        ->where('name', $permissao)
        ->where('guard_name', 'admin')
        ->firstOrFail();

    return PermissionGrant::create([
        'admin_user_id' => $user->id,
        'permission_id' => $permission->id,
        'type' => $tipo,
        'reason' => 'teste',
        'expires_at' => $expiraEm,
    ]);
}
