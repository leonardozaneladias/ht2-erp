<?php

declare(strict_types=1);

namespace App\Enums;

enum ModuloAcesso: string
{
    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::Empresas => 'Empresas',
            self::Usuarios => 'Usuários',
            self::Perfis => 'Perfis',
            self::Acessos => 'Controle de Acesso',
            self::Auditoria => 'Auditoria',
            self::Configuracoes => 'Configurações',
            self::Notificacoes => 'Notificações',
            self::Sistema => 'Sistema',
        };
    }

    public function descricao(): string
    {
        return match ($this) {
            self::Dashboard => 'Painel inicial e indicadores.',
            self::Empresas => 'Cadastro de empresas e filiais (multi-tenant).',
            self::Usuarios => 'Gestão de usuários administrativos.',
            self::Perfis => 'Gestão de perfis (papéis) e suas permissões.',
            self::Acessos => 'Concessões diretas, matriz e governança de acesso.',
            self::Auditoria => 'Trilhas de auditoria e histórico de mudanças.',
            self::Configuracoes => 'Configurações gerais do sistema.',
            self::Notificacoes => 'Envio de comunicados in-app aos usuários.',
            self::Sistema => 'Ferramentas operacionais (filas, monitoramento).',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Dashboard => 'info',
            self::Empresas => 'primary',
            self::Usuarios => 'primary',
            self::Perfis => 'secondary',
            self::Acessos => 'warning',
            self::Auditoria, self::Configuracoes, self::Sistema => 'neutral',
            self::Notificacoes => 'info',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Dashboard => 'tabler--dashboard',
            self::Empresas => 'tabler--building-community',
            self::Usuarios => 'tabler--users',
            self::Perfis => 'tabler--shield-lock',
            self::Acessos => 'tabler--lock-access',
            self::Auditoria => 'tabler--history',
            self::Configuracoes => 'tabler--settings',
            self::Notificacoes => 'tabler--bell',
            self::Sistema => 'tabler--server-cog',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    case Dashboard = 'dashboard';
    case Empresas = 'empresas';
    case Usuarios = 'usuarios';
    case Perfis = 'perfis';
    case Acessos = 'acessos';
    case Auditoria = 'auditoria';
    case Configuracoes = 'configuracoes';
    case Notificacoes = 'notificacoes';
    case Sistema = 'sistema';
}
