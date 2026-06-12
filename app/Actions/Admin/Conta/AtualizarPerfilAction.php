<?php

declare(strict_types=1);

namespace App\Actions\Admin\Conta;

use App\Actions\Admin\AtualizarAvatarAction;
use App\DTOs\Admin\PerfilContaDTO;
use App\Models\AdminUser;
use Illuminate\Http\UploadedFile;

/**
 * Atualiza o perfil do próprio usuário (dados pessoais + avatar). O avatar é
 * delegado à AtualizarAvatarAction (sem ator => sem auditoria — gestão pessoal).
 */
final class AtualizarPerfilAction
{
    public function __construct(private readonly AtualizarAvatarAction $avatarAction) {}

    public function execute(AdminUser $usuario, PerfilContaDTO $dados, ?UploadedFile $avatar): void
    {
        $usuario->fill($dados->paraModel());
        $usuario->save();

        if ($avatar !== null) {
            $this->avatarAction->execute($usuario, $avatar);
        }
    }

    public function removerAvatar(AdminUser $usuario): void
    {
        $this->avatarAction->remover($usuario);
    }
}
