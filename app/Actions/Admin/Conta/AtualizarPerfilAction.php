<?php

declare(strict_types=1);

namespace App\Actions\Admin\Conta;

use App\DTOs\Admin\PerfilContaDTO;
use App\Models\AdminUser;
use App\Services\Admin\Settings\SettingsFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Atualiza o perfil do próprio usuário (dados pessoais + avatar). O avatar é
 * gravado no disco público, substituindo o anterior; removê-lo volta ao
 * fallback de iniciais.
 */
final class AtualizarPerfilAction
{
    public function __construct(private readonly SettingsFileUploadService $upload) {}

    public function execute(AdminUser $usuario, PerfilContaDTO $dados, ?UploadedFile $avatar): void
    {
        $usuario->fill($dados->paraModel());

        if ($avatar !== null) {
            $usuario->avatar_url = $this->upload->substituir($avatar, (string) $usuario->avatar_url, 'avatars');
        }

        $usuario->save();
    }

    public function removerAvatar(AdminUser $usuario): void
    {
        if ($usuario->avatar_url !== null && $usuario->avatar_url !== '') {
            Storage::disk('public')->delete($usuario->avatar_url);
        }

        $usuario->avatar_url = null;
        $usuario->save();
    }
}
