<?php

declare(strict_types=1);

namespace App\Actions\Admin\Notificacoes;

use App\DTOs\Admin\ComunicadoDTO;
use App\Enums\PublicoComunicado;
use App\Models\AdminUser;
use App\Notifications\ComunicadoNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Compõe e envia um comunicado in-app aos usuários do público escolhido,
 * registrando a ação na auditoria. Retorna o número de destinatários.
 */
final class EnviarComunicadoAction
{
    public function execute(ComunicadoDTO $dto): int
    {
        return DB::transaction(function () use ($dto): int {
            $destinatarios = $this->resolverDestinatarios($dto);

            Notification::send(
                $destinatarios,
                new ComunicadoNotification($dto->tipo, $dto->titulo, $dto->mensagem),
            );

            activity('notificacoes')
                ->causedBy(Auth::guard('admin')->user())
                ->withProperties([
                    'titulo' => $dto->titulo,
                    'tipo' => $dto->tipo->value,
                    'publico' => $dto->publico->value,
                    'papel' => $dto->papel,
                    'destinatarios' => $destinatarios->count(),
                ])
                ->event('comunicado_enviado')
                ->log('Comunicado enviado');

            return $destinatarios->count();
        });
    }

    /**
     * @return Collection<int, AdminUser>
     */
    private function resolverDestinatarios(ComunicadoDTO $dto): Collection
    {
        $consulta = AdminUser::query()->where('ativo', true);

        if ($dto->publico === PublicoComunicado::Papel && $dto->papel !== null) {
            $consulta->role($dto->papel, 'admin');
        }

        return $consulta->get();
    }
}
