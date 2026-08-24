<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin;

use HT2ML\Core\Models\AdminUser;
use HT2ML\Core\Services\Admin\Settings\SettingsFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Atualiza/remove o avatar de um usuário admin. Reutilizada pelo Minha Conta
 * (próprio usuário) e pelo cadastro de usuários (admin gerindo terceiros).
 * A mudança de `avatar_url` entra na auditoria via trait Auditavel.
 */
class AtualizarAvatarAction
{
    public function __construct(private readonly SettingsFileUploadService $upload) {}

    public function execute(AdminUser $usuario, UploadedFile $avatar): void
    {
        $usuario->avatar_url = $this->upload->substituir($avatar, (string) $usuario->avatar_url, 'avatars');
        $usuario->save();
    }

    public function remover(AdminUser $usuario): void
    {
        if ($usuario->avatar_url !== null && $usuario->avatar_url !== '') {
            Storage::disk('public')->delete($usuario->avatar_url);
        }

        $usuario->avatar_url = null;
        $usuario->save();
    }
}
