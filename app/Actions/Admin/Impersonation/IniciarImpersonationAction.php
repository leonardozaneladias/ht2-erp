<?php

declare(strict_types=1);

namespace App\Actions\Admin\Impersonation;

use App\Exceptions\AccessException;
use App\Models\AdminUser;
use App\Services\Admin\AccessResolver;
use App\Services\Admin\HierarchyResolver;
use App\Services\Admin\Security\AlertaSeguranca;
use App\Support\Impersonation\ImpersonationContext;
use Illuminate\Support\Facades\Auth;

/**
 * Inicia uma personificação (act-as). Revalida a elegibilidade no servidor
 * (defense-in-depth), registra o evento de auditoria com o ator real como causer,
 * grava o contexto e troca o usuário autenticado para o alvo.
 */
final class IniciarImpersonationAction
{
    public function __construct(
        private readonly HierarchyResolver $hierarchy,
        private readonly ImpersonationContext $context,
        private readonly AccessResolver $accessResolver,
        private readonly AlertaSeguranca $alerta,
    ) {}

    public function execute(AdminUser $ator, AdminUser $alvo, string $motivo): void
    {
        $this->garantirElegivel($ator, $alvo);

        // Logado ANTES de ativar o contexto: causer = ator real e o listener de
        // auditoria (Activity::creating) não marca este evento como personificação.
        activity('impersonation')
            ->causedBy($ator)
            ->performedOn($alvo)
            ->event('iniciada')
            ->withProperties(['motivo' => $motivo])
            ->log('Personificação iniciada');

        $this->context->iniciar((int) $ator->getKey(), $motivo);
        Auth::guard('admin')->login($alvo);
        $this->accessResolver->invalidar($alvo);
        $this->alerta->personificacaoIniciada($ator, $alvo);
    }

    private function garantirElegivel(AdminUser $ator, AdminUser $alvo): void
    {
        if ($this->context->ativo()) {
            throw new AccessException('Encerre a personificação atual antes de iniciar outra.');
        }

        if ($ator->is($alvo)) {
            throw new AccessException('Você não pode personificar a si mesmo.');
        }

        if (! $alvo->ativo) {
            throw new AccessException('Não é possível personificar um usuário inativo.');
        }

        if ($this->ehSuperAdmin($alvo)) {
            throw new AccessException('Não é possível personificar um super-administrador.');
        }

        if (! $this->hierarchy->podeGerir($ator, $alvo)) {
            throw new AccessException('Você não tem hierarquia para personificar este usuário.');
        }

        if (! $this->ehSuperAdmin($ator) && ! $this->compartilhaEmpresaAtiva($ator, $alvo)) {
            throw new AccessException('Este usuário não pertence a uma empresa que você acessa.');
        }
    }

    private function compartilhaEmpresaAtiva(AdminUser $ator, AdminUser $alvo): bool
    {
        $empresaAtiva = $ator->empresa_ativa_id;

        return $empresaAtiva !== null && $alvo->temAcessoAEmpresa((int) $empresaAtiva);
    }

    private function ehSuperAdmin(AdminUser $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains('name', (string) config('access.super_admin_role', 'super-admin'));
    }
}
