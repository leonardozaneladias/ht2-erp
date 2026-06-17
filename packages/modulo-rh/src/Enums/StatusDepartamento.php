<?php

declare(strict_types=1);

namespace HT2ERP\Rh\Enums;

enum StatusDepartamento: string
{
    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }

    /**
     * Variante visual do badge de status (x-shared.badge).
     */
    public function variant(): string
    {
        return match ($this) {
            self::Ativo => 'success',
            self::Inativo => 'default',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $caso): array => ['value' => $caso->value, 'label' => $caso->label()],
            self::cases(),
        );
    }

    case Ativo = 'ativo';
    case Inativo = 'inativo';
}
