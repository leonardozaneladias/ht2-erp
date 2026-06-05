<?php

declare(strict_types=1);

namespace App\Actions\Admin\Impersonation;

use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Support\Facades\Auth;

/**
 * Encerra a personificação: registra o evento de fim, limpa o contexto e
 * restaura o usuário original. Se o original ficou inválido (desativado/excluído),
 * faz logout completo por segurança.
 */
final class EncerrarImpersonationAction
{
    public function __construct(
        private readonly ImpersonationContext $context,
        private readonly AccessResolver $accessResolver,
    ) {}

    public function execute(): void
    {
        if (! $this->context->ativo()) {
            return;
        }

        $originalId = $this->context->originalId();
        $original = $originalId !== null ? AdminUser::find($originalId) : null;
        $alvo = Auth::guard('admin')->user();

        // Contexto encerrado ANTES de logar: o evento de fim não é marcado como
        // personificação e o causer é o ator real.
        $this->context->encerrar();

        activity('impersonation')
            ->causedBy($original ?? $alvo)
            ->performedOn($alvo)
            ->event('encerrada')
            ->log('Personificação encerrada');

        if ($original instanceof AdminUser && $original->ativo) {
            Auth::guard('admin')->login($original);
            $this->accessResolver->invalidar($original);

            return;
        }

        Auth::guard('admin')->logout();
    }
}
