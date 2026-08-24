<?php

declare(strict_types=1);

namespace HT2ML\Core\Actions\Admin\Lgpd;

use HT2ML\Core\Models\Activity;
use HT2ML\Core\Models\AdminUser;

/**
 * Monta os dados pessoais de um usuário admin para export LGPD (acesso/
 * portabilidade). NUNCA inclui o secret/recovery do 2FA — apenas o status.
 */
final class ExportarDadosUsuarioAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(AdminUser $usuario): array
    {
        $usuario->loadMissing([
            'roles', 'empresasAcessiveis', 'filiaisAcessiveis', 'papeisPorEmpresa',
            'permissionGrants.permission',
        ]);

        return [
            'perfil' => [
                'id' => $usuario->id,
                'nome' => $usuario->getAttribute('nome'),
                'email' => $usuario->getAttribute('email'),
                'avatar_url' => $usuario->getAttribute('avatar_url'),
                'ativo' => (bool) $usuario->getAttribute('ativo'),
                'anonimizado_em' => $usuario->anonimizado_em?->toIso8601String(),
                'ultimo_login_em' => $usuario->last_login_at?->toIso8601String(),
                'ultimo_login_ip' => $usuario->getAttribute('last_login_ip'),
                'dois_fatores_ativo' => $usuario->hasTwoFactorEnabled(),
                'criado_em' => $usuario->created_at?->toIso8601String(),
            ],
            'acessos' => [
                'papeis_globais' => $usuario->roles->pluck('name')->all(),
                'empresas' => $usuario->empresasAcessiveis->pluck('nome')->all(),
                'filiais' => $usuario->filiaisAcessiveis->pluck('nome')->all(),
                'papeis_por_empresa' => $usuario->papeisPorEmpresa
                    ->map(static fn ($r): array => [
                        'papel' => $r->name,
                        'empresa_id' => $r->getAttribute('pivot')?->getAttribute('empresa_id'),
                    ])->all(),
                'concessoes_diretas' => $usuario->permissionGrants
                    ->map(static fn ($g): array => [
                        'permissao' => $g->permission?->getAttribute('name'),
                        'tipo' => $g->getAttribute('type')?->value,
                        'motivo' => $g->getAttribute('reason'),
                        'expira_em' => $g->expires_at?->toIso8601String(),
                    ])->all(),
            ],
            'atividades' => Activity::query()
                ->where(function ($q) use ($usuario): void {
                    $q->where(['causer_type' => AdminUser::class, 'causer_id' => $usuario->id])
                        ->orWhere(['subject_type' => AdminUser::class, 'subject_id' => $usuario->id]);
                })
                ->latest('id')
                ->limit(1000)
                ->get()
                ->map(static fn (Activity $a): array => [
                    'data' => $a->created_at?->toIso8601String(),
                    'log' => $a->log_name,
                    'evento' => $a->event,
                    'descricao' => $a->description,
                ])->all(),
        ];
    }
}
