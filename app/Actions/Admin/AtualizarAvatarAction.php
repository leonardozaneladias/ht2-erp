<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\AdminUser;
use App\Services\Admin\Settings\SettingsFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Atualiza/remove o avatar de um usuário admin. Reutilizada pelo Minha Conta
 * (próprio usuário) e pelo cadastro de usuários (admin gerindo terceiros).
 * Quando o ator é outra pessoa, a mudança entra na trilha de auditoria.
 */
class AtualizarAvatarAction
{
    public function __construct(private readonly SettingsFileUploadService $upload) {}

    public function execute(AdminUser $usuario, UploadedFile $avatar, ?AdminUser $ator = null): void
    {
        $usuario->avatar_url = $this->upload->substituir($avatar, (string) $usuario->avatar_url, 'avatars');
        $usuario->save();

        $this->auditar($usuario, $ator, 'avatar_updated', 'Foto do usuário atualizada');
    }

    public function remover(AdminUser $usuario, ?AdminUser $ator = null): void
    {
        if ($usuario->avatar_url !== null && $usuario->avatar_url !== '') {
            Storage::disk('public')->delete($usuario->avatar_url);
        }

        $usuario->avatar_url = null;
        $usuario->save();

        $this->auditar($usuario, $ator, 'avatar_removed', 'Foto do usuário removida');
    }

    /**
     * Audita apenas quando um admin altera a foto de OUTRO usuário — a troca
     * da própria foto é gestão pessoal, sem valor de trilha.
     */
    private function auditar(AdminUser $usuario, ?AdminUser $ator, string $evento, string $descricao): void
    {
        if ($ator === null || $ator->id === $usuario->id) {
            return;
        }

        activity('admin_users')
            ->performedOn($usuario)
            ->causedBy($ator)
            ->event($evento)
            ->log($descricao);
    }
}
