<?php

declare(strict_types=1);

namespace App\Enums;

enum OrigemAcesso: string
{
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super-admin',
            self::Deny => 'Negação direta',
            self::GrantDireto => 'Concessão direta',
            self::Role => 'Perfil',
            self::Ausente => 'Sem acesso',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::SuperAdmin => 'warning',
            self::Deny => 'danger',
            self::GrantDireto => 'success',
            self::Role => 'primary',
            self::Ausente => 'neutral',
        };
    }

    public function permitido(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::GrantDireto, self::Role => true,
            self::Deny, self::Ausente => false,
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

    case SuperAdmin = 'super_admin';
    case Deny = 'deny';
    case GrantDireto = 'grant_direto';
    case Role = 'role';
    case Ausente = 'ausente';
}
