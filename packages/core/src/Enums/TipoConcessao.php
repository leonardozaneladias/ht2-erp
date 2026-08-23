<?php

declare(strict_types=1);

namespace HT2ML\Core\Enums;

enum TipoConcessao: string
{
    public function label(): string
    {
        return match ($this) {
            self::Grant => 'Concessão',
            self::Deny => 'Negação',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Grant => 'success',
            self::Deny => 'danger',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Grant => 'tabler--plus',
            self::Deny => 'tabler--ban',
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

    case Grant = 'grant';
    case Deny = 'deny';
}
